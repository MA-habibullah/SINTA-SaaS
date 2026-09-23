<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { useMemorySecurity } from '@/Utils/cryptoSecurity.js';

const props = defineProps({
  suratMasukList: Object,
  suratKeluarList: Object,
  stats: Object,
  filters: Object,
});

const activeTab = ref(props.filters?.tab || 'surat_masuk');
const search = ref(props.filters?.search || '');
const filterStatusDisposisi = ref(props.filters?.status_disposisi || '');
const filterStatusSurat = ref(props.filters?.status_surat || '');

const state = ref({
  modalSuratMasuk: false,
  isEditSuratMasuk: false,
  modalSuratKeluar: false,
  isEditSuratKeluar: false,
  modalDisposisi: false,
  selectedSuratMasuk: null,
});

useMemorySecurity([state]);

// Standarisasi Opsi Dropdown
const sifatSuratOptions = [
  { id: 'Biasa', nama: 'Biasa' },
  { id: 'Penting', nama: 'Penting' },
  { id: 'Segera', nama: 'Segera' },
  { id: 'Rahasia', nama: 'Rahasia' },
];

const kategoriSuratOptions = [
  { id: 'Dinas', nama: 'Dinas' },
  { id: 'Undangan', nama: 'Undangan' },
  { id: 'Pemberitahuan', nama: 'Pemberitahuan' },
  { id: 'Lainnya', nama: 'Lainnya' },
];

const statusSuratOptions = [
  { id: 'Draft', nama: 'Draft' },
  { id: 'Menunggu Persetujuan', nama: 'Menunggu Persetujuan' },
  { id: 'Disetujui', nama: 'Disetujui' },
  { id: 'Diterbitkan', nama: 'Diterbitkan' },
  { id: 'Terkirim', nama: 'Terkirim' },
];

const diteruskanKepadaOptions = [
  { id: 'Waka Kurikulum', nama: 'Waka Kurikulum' },
  { id: 'Waka Kesiswaan', nama: 'Waka Kesiswaan' },
  { id: 'Waka Sarpras', nama: 'Waka Sarpras' },
  { id: 'Kepala Tata Usaha', nama: 'Kepala Tata Usaha' },
  { id: 'Bendahara Sekolah', nama: 'Bendahara Sekolah' },
  { id: 'Guru BK', nama: 'Guru BK' },
];

// Form Surat Masuk
const formSuratMasuk = useForm({
  id: null,
  nomor_agenda: '',
  nomor_surat_asal: '',
  tanggal_surat: new Date().toISOString().split('T')[0],
  tanggal_diterima: new Date().toISOString().split('T')[0],
  pengirim: '',
  perihal: '',
  kategori_surat: 'Dinas',
  sifat_surat: 'Biasa',
  ringkasan_isi: '',
  file_surat_url: '',
});

// Form Surat Keluar
const formSuratKeluar = useForm({
  id: null,
  nomor_surat: '',
  klasifikasi_kode: '',
  tujuan_surat: '',
  tanggal_surat: new Date().toISOString().split('T')[0],
  perihal: '',
  ringkasan_isi: '',
  penandatangan_nama: 'Dr. H. Ahmad Sudrajat, M.Pd',
  penandatangan_jabatan: 'Kepala Sekolah',
  status_surat: 'Diterbitkan',
  file_surat_url: '',
});

// Form Disposisi
const formDisposisi = useForm({
  surat_masuk_id: null,
  dari_jabatan: 'Kepala Sekolah',
  diteruskan_kepada: 'Waka Kurikulum',
  instruksi_disposisi: 'Tindak lanjuti dan laporkan hasilnya.',
  catatan_tambahan: '',
  batas_waktu_tindak_lanjut: '',
});

// Search & Filter Trigger
const applyFilter = () => {
  router.get(window.location.pathname, {
    tab: activeTab.value,
    search: search.value || undefined,
    status_disposisi: filterStatusDisposisi.value || undefined,
    status_surat: filterStatusSurat.value || undefined,
  }, {
    preserveState: true,
    preserveScroll: true,
  });
};

const switchTab = (tab) => {
  activeTab.value = tab;
  applyFilter();
};

// Modal Actions Surat Masuk
const openCreateSuratMasuk = () => {
  formSuratMasuk.reset();
  formSuratMasuk.nomor_agenda = 'AGD-' + String(Math.floor(1000 + Math.random() * 9000));
  state.value.isEditSuratMasuk = false;
  state.value.modalSuratMasuk = true;
};

const openEditSuratMasuk = (item) => {
  formSuratMasuk.id = item.id;
  formSuratMasuk.nomor_agenda = item.nomor_agenda || '';
  formSuratMasuk.nomor_surat_asal = item.nomor_surat_asal || '';
  formSuratMasuk.tanggal_surat = item.tanggal_surat || '';
  formSuratMasuk.tanggal_diterima = item.tanggal_diterima || '';
  formSuratMasuk.pengirim = item.pengirim || '';
  formSuratMasuk.perihal = item.perihal || '';
  formSuratMasuk.kategori_surat = item.kategori_surat || 'Dinas';
  formSuratMasuk.sifat_surat = item.sifat_surat || 'Biasa';
  formSuratMasuk.ringkasan_isi = item.ringkasan_isi || '';
  formSuratMasuk.file_surat_url = item.file_surat_url || '';
  state.value.isEditSuratMasuk = true;
  state.value.modalSuratMasuk = true;
};

const submitSuratMasuk = () => {
  if (state.value.isEditSuratMasuk) {
    formSuratMasuk.put(`/persuratan/surat-masuk/${formSuratMasuk.id}`, {
      onSuccess: () => { state.value.modalSuratMasuk = false; }
    });
  } else {
    formSuratMasuk.post('/persuratan/surat-masuk', {
      onSuccess: () => { state.value.modalSuratMasuk = false; }
    });
  }
};

const deleteSuratMasuk = (id) => {
  if (confirm('Apakah Anda yakin ingin menghapus catatan surat masuk ini?')) {
    router.delete(`/persuratan/surat-masuk/${id}`);
  }
};

// Modal Actions Surat Keluar
const openCreateSuratKeluar = () => {
  formSuratKeluar.reset();
  const year = new Date().getFullYear();
  formSuratKeluar.nomor_surat = `421.1/${Math.floor(100 + Math.random() * 900)}/SMK-SINTA/${year}`;
  state.value.isEditSuratKeluar = false;
  state.value.modalSuratKeluar = true;
};

const openEditSuratKeluar = (item) => {
  formSuratKeluar.id = item.id;
  formSuratKeluar.nomor_surat = item.nomor_surat || '';
  formSuratKeluar.klasifikasi_kode = item.klasifikasi_kode || '';
  formSuratKeluar.tujuan_surat = item.tujuan_surat || '';
  formSuratKeluar.tanggal_surat = item.tanggal_surat || '';
  formSuratKeluar.perihal = item.perihal || '';
  formSuratKeluar.ringkasan_isi = item.ringkasan_isi || '';
  formSuratKeluar.penandatangan_nama = item.penandatangan_nama || '';
  formSuratKeluar.penandatangan_jabatan = item.penandatangan_jabatan || '';
  formSuratKeluar.status_surat = item.status_surat || 'Diterbitkan';
  formSuratKeluar.file_surat_url = item.file_surat_url || '';
  state.value.isEditSuratKeluar = true;
  state.value.modalSuratKeluar = true;
};

const submitSuratKeluar = () => {
  if (state.value.isEditSuratKeluar) {
    formSuratKeluar.put(`/persuratan/surat-keluar/${formSuratKeluar.id}`, {
      onSuccess: () => { state.value.modalSuratKeluar = false; }
    });
  } else {
    formSuratKeluar.post('/persuratan/surat-keluar', {
      onSuccess: () => { state.value.modalSuratKeluar = false; }
    });
  }
};

const deleteSuratKeluar = (id) => {
  if (confirm('Apakah Anda yakin ingin menghapus arsip surat keluar ini?')) {
    router.delete(`/persuratan/surat-keluar/${id}`);
  }
};

// Modal Actions Disposisi
const openDisposisiModal = (surat) => {
  state.value.selectedSuratMasuk = surat;
  formDisposisi.reset();
  formDisposisi.surat_masuk_id = surat.id;
  state.value.modalDisposisi = true;
};

const submitDisposisi = () => {
  formDisposisi.post('/persuratan/disposisi', {
    onSuccess: () => { state.value.modalDisposisi = false; }
  });
};

const formatDate = (dateStr) => {
  if (!dateStr) return '-';
  const d = new Date(dateStr);
  return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
};
</script>

<template>
  <AppLayout title="Persuratan & Disposisi">
    <Head title="Persuratan & E-Disposisi" />

    <div class="space-y-6 pb-12">
      <!-- 1. HEADER & KPI CARDS -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 class="text-2xl font-black text-slate-800 tracking-tight flex items-center gap-2.5">
            <i class="bi bi-envelope-paper-fill text-indigo-600"></i>
            Persuratan & E-Disposisi Digital
          </h1>
          <p class="text-xs sm:text-sm text-slate-500 mt-1">
            Tata kelola buku agenda surat masuk, penerbitan arsip surat keluar, dan lembar disposisi digital pimpinan sekolah.
          </p>
        </div>

        <!-- Quick Action Buttons -->
        <div class="flex items-center gap-2.5 shrink-0">
          <button
            @click="openCreateSuratMasuk"
            class="px-4 py-2.5 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-bold text-xs shadow-md transition inline-flex items-center gap-1.5"
          >
            <i class="bi bi-inbox-fill"></i>
            <span>Catat Surat Masuk</span>
          </button>
          <button
            @click="openCreateSuratKeluar"
            class="px-4 py-2.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white font-bold text-xs shadow-md transition inline-flex items-center gap-1.5"
          >
            <i class="bi bi-send-fill"></i>
            <span>Buat Surat Keluar</span>
          </button>
        </div>
      </div>

      <!-- KPI Summary Grid -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-2xs">
          <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Total Surat Masuk</span>
            <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-lg">
              <i class="bi bi-inbox-fill"></i>
            </div>
          </div>
          <div class="text-3xl font-black text-slate-800">{{ stats?.total_surat_masuk || 0 }}</div>
          <div class="text-xs text-blue-600 font-medium mt-2">Buku Agenda Terdaftar</div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-2xs">
          <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Total Surat Keluar</span>
            <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg">
              <i class="bi bi-send-check-fill"></i>
            </div>
          </div>
          <div class="text-3xl font-black text-slate-800">{{ stats?.total_surat_keluar || 0 }}</div>
          <div class="text-xs text-emerald-600 font-medium mt-2">Arsip Diterbitkan</div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-2xs">
          <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Menunggu Disposisi</span>
            <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-lg">
              <i class="bi bi-hourglass-split"></i>
            </div>
          </div>
          <div class="text-3xl font-black text-slate-800">{{ stats?.menunggu_disposisi || 0 }}</div>
          <div class="text-xs text-amber-600 font-medium mt-2">Perlu Tindak Lanjut Pimpinan</div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-2xs">
          <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Disposisi Selesai</span>
            <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-lg">
              <i class="bi bi-check2-all"></i>
            </div>
          </div>
          <div class="text-3xl font-black text-slate-800">{{ stats?.disposisi_selesai || 0 }}</div>
          <div class="text-xs text-purple-600 font-medium mt-2">Telah Ditindaklanjuti</div>
        </div>
      </div>

      <!-- 2. TABBED VIEW & FILTER BAR -->
      <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden">
        <!-- Tab Buttons Header -->
        <div class="p-4 border-b border-slate-100 flex flex-col md:flex-row md:items-center justify-between gap-4">
          <div class="flex items-center gap-2">
            <button
              @click="switchTab('surat_masuk')"
              class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2"
              :class="activeTab === 'surat_masuk' ? 'bg-indigo-600 text-white shadow-md' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
            >
              <i class="bi bi-inbox"></i>
              <span>Agenda Surat Masuk ({{ suratMasukList?.total || 0 }})</span>
            </button>
            <button
              @click="switchTab('surat_keluar')"
              class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2"
              :class="activeTab === 'surat_keluar' ? 'bg-emerald-600 text-white shadow-md' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
            >
              <i class="bi bi-send"></i>
              <span>Arsip Surat Keluar ({{ suratKeluarList?.total || 0 }})</span>
            </button>
          </div>

          <!-- Search Bar -->
          <div class="flex items-center gap-2 w-full md:w-auto">
            <div class="relative flex-1 md:w-64">
              <i class="bi bi-search absolute left-3 top-2.5 text-slate-400 text-xs"></i>
              <input
                v-model="search"
                @keyup.enter="applyFilter"
                type="text"
                placeholder="Cari perihal, nomor, pengirim..."
                class="w-full pl-9 pr-3 py-2 rounded-xl border border-slate-200 text-xs text-slate-700 focus:ring-2 focus:ring-indigo-500"
              />
            </div>
            <button
              @click="applyFilter"
              class="px-3 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition"
            >
              Cari
            </button>
          </div>
        </div>

        <!-- 3A. TAB KONTEN 1: SURAT MASUK -->
        <div v-if="activeTab === 'surat_masuk'" class="overflow-x-auto">
          <table class="w-full text-left text-xs text-slate-600">
            <thead class="bg-slate-50 text-slate-500 uppercase font-semibold">
              <tr>
                <th class="px-5 py-3">No. Agenda & Tanggal</th>
                <th class="px-5 py-3">Surat Asal & Pengirim</th>
                <th class="px-5 py-3">Perihal & Sifat</th>
                <th class="px-5 py-3">Status Disposisi</th>
                <th class="px-5 py-3 text-right">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="s in suratMasukList?.data || []" :key="s.id" class="hover:bg-slate-50/80 transition">
                <td class="px-5 py-3.5">
                  <div class="font-bold text-slate-800 font-mono">{{ s.nomor_agenda || '-' }}</div>
                  <div class="text-[11px] text-slate-400">Diterima: {{ formatDate(s.tanggal_diterima) }}</div>
                </td>
                <td class="px-5 py-3.5">
                  <div class="font-bold text-slate-800">{{ s.pengirim }}</div>
                  <div class="text-[11px] text-slate-500 font-mono">No: {{ s.nomor_surat_asal }}</div>
                </td>
                <td class="px-5 py-3.5">
                  <div class="font-bold text-slate-800 max-w-xs truncate">{{ s.perihal }}</div>
                  <div class="flex items-center gap-1.5 mt-0.5">
                    <span
                      class="px-2 py-0.5 rounded text-[10px] font-bold"
                      :class="{
                        'bg-rose-100 text-rose-800': s.sifat_surat === 'Penting' || s.sifat_surat === 'Rahasia',
                        'bg-amber-100 text-amber-800': s.sifat_surat === 'Segera',
                        'bg-slate-100 text-slate-700': s.sifat_surat === 'Biasa',
                      }"
                    >
                      {{ s.sifat_surat }}
                    </span>
                    <span class="text-[11px] text-slate-400">• {{ s.kategori_surat || 'Dinas' }}</span>
                  </div>
                </td>
                <td class="px-5 py-3.5">
                  <span
                    class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider"
                    :class="{
                      'bg-emerald-100 text-emerald-800': s.status_disposisi === 'Sudah Disposisi' || s.status_disposisi === 'Selesai',
                      'bg-amber-100 text-amber-800': s.status_disposisi === 'Belum Disposisi' || s.status_disposisi === 'Menunggu Disposisi',
                    }"
                  >
                    {{ s.status_disposisi }}
                  </span>
                </td>
                <td class="px-5 py-3.5 text-right space-x-1">
                  <button
                    @click="openDisposisiModal(s)"
                    class="px-2.5 py-1.5 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-bold text-xs transition"
                    title="Terbitkan Lembar Disposisi"
                  >
                    <i class="bi bi-file-earmark-text"></i> Disposisi
                  </button>
                  <button
                    @click="openEditSuratMasuk(s)"
                    class="px-2.5 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition"
                    title="Edit Data"
                  >
                    <i class="bi bi-pencil"></i>
                  </button>
                  <button
                    @click="deleteSuratMasuk(s.id)"
                    class="px-2.5 py-1.5 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-600 font-bold text-xs transition"
                    title="Hapus"
                  >
                    <i class="bi bi-trash"></i>
                  </button>
                </td>
              </tr>
              <tr v-if="!suratMasukList?.data?.length">
                <td colspan="5" class="px-5 py-12 text-center">
                  <div class="max-w-sm mx-auto flex flex-col items-center justify-center space-y-3">
                    <div class="w-14 h-14 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-2xl shadow-inner">
                      <i class="bi bi-inbox"></i>
                    </div>
                    <div class="text-sm font-bold text-slate-700">Belum Ada Agenda Surat Masuk</div>
                    <p class="text-xs text-slate-400">Seluruh surat resmi yang diterima dari instansi, dinas, atau pihak luar akan tercatat di buku agenda digital ini.</p>
                    <button
                      type="button"
                      @click="openCreateSuratMasuk"
                      class="mt-2 px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-md transition inline-flex items-center gap-1.5"
                    >
                      <i class="bi bi-plus-lg"></i>
                      <span>Catat Surat Masuk Pertama</span>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- 3B. TAB KONTEN 2: SURAT KELUAR -->
        <div v-if="activeTab === 'surat_keluar'" class="overflow-x-auto">
          <table class="w-full text-left text-xs text-slate-600">
            <thead class="bg-slate-50 text-slate-500 uppercase font-semibold">
              <tr>
                <th class="px-5 py-3">Nomor Surat & Tanggal</th>
                <th class="px-5 py-3">Tujuan Surat</th>
                <th class="px-5 py-3">Perihal & Ringkasan</th>
                <th class="px-5 py-3">Penandatangan</th>
                <th class="px-5 py-3">Status</th>
                <th class="px-5 py-3 text-right">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="sk in suratKeluarList?.data || []" :key="sk.id" class="hover:bg-slate-50/80 transition">
                <td class="px-5 py-3.5">
                  <div class="font-bold text-slate-800 font-mono">{{ sk.nomor_surat }}</div>
                  <div class="text-[11px] text-slate-400">Tanggal: {{ formatDate(sk.tanggal_surat) }}</div>
                </td>
                <td class="px-5 py-3.5">
                  <div class="font-bold text-slate-800">{{ sk.tujuan_surat }}</div>
                </td>
                <td class="px-5 py-3.5">
                  <div class="font-bold text-slate-800 max-w-xs truncate">{{ sk.perihal }}</div>
                  <div class="text-[11px] text-slate-400 truncate max-w-xs">{{ sk.ringkasan_isi || '-' }}</div>
                </td>
                <td class="px-5 py-3.5">
                  <div class="font-bold text-slate-800">{{ sk.penandatangan_nama || 'Kepala Sekolah' }}</div>
                  <div class="text-[11px] text-slate-400">{{ sk.penandatangan_jabatan || 'Kepala Sekolah' }}</div>
                </td>
                <td class="px-5 py-3.5">
                  <span
                    class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider"
                    :class="{
                      'bg-emerald-100 text-emerald-800': sk.status_surat === 'Diterbitkan' || sk.status_surat === 'Disetujui',
                      'bg-blue-100 text-blue-800': sk.status_surat === 'Terkirim',
                      'bg-amber-100 text-amber-800': sk.status_surat === 'Menunggu Persetujuan',
                      'bg-slate-100 text-slate-700': sk.status_surat === 'Draft',
                    }"
                  >
                    {{ sk.status_surat }}
                  </span>
                </td>
                <td class="px-5 py-3.5 text-right space-x-1">
                  <button
                    @click="openEditSuratKeluar(sk)"
                    class="px-2.5 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition"
                    title="Edit Data"
                  >
                    <i class="bi bi-pencil"></i>
                  </button>
                  <button
                    @click="deleteSuratKeluar(sk.id)"
                    class="px-2.5 py-1.5 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-600 font-bold text-xs transition"
                    title="Hapus"
                  >
                    <i class="bi bi-trash"></i>
                  </button>
                </td>
              </tr>
              <tr v-if="!suratKeluarList?.data?.length">
                <td colspan="6" class="px-5 py-12 text-center">
                  <div class="max-w-sm mx-auto flex flex-col items-center justify-center space-y-3">
                    <div class="w-14 h-14 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-2xl shadow-inner">
                      <i class="bi bi-send-check"></i>
                    </div>
                    <div class="text-sm font-bold text-slate-700">Belum Ada Arsip Surat Keluar</div>
                    <p class="text-xs text-slate-400">Penerbitan surat keluar dinas, surat tugas guru, dan undangan resmi sekolah akan terarsip otomatis di sini.</p>
                    <button
                      type="button"
                      @click="openCreateSuratKeluar"
                      class="mt-2 px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-md transition inline-flex items-center gap-1.5"
                    >
                      <i class="bi bi-plus-lg"></i>
                      <span>Terbitkan Surat Keluar Baru</span>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- 4. MODAL FORM SURAT MASUK -->
    <div v-if="state.modalSuratMasuk" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
      <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-100 space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <h3 class="font-bold text-slate-800 text-base">
            {{ state.isEditSuratMasuk ? 'Edit Data Surat Masuk' : 'Pencatatan Surat Masuk Baru' }}
          </h3>
          <button @click="state.modalSuratMasuk = false" class="text-slate-400 hover:text-slate-600"><i class="bi bi-x-lg"></i></button>
        </div>

        <form @submit.prevent="submitSuratMasuk" class="space-y-3 text-xs">
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="font-bold text-slate-700 block mb-1">No. Agenda</label>
              <input v-model="formSuratMasuk.nomor_agenda" type="text" required class="w-full p-2.5 rounded-xl border border-slate-200" />
            </div>
            <div>
              <label class="font-bold text-slate-700 block mb-1">No. Surat Asal</label>
              <input v-model="formSuratMasuk.nomor_surat_asal" type="text" required class="w-full p-2.5 rounded-xl border border-slate-200" />
            </div>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="font-bold text-slate-700 block mb-1">Tanggal Surat</label>
              <input v-model="formSuratMasuk.tanggal_surat" type="date" required class="w-full p-2.5 rounded-xl border border-slate-200" />
            </div>
            <div>
              <label class="font-bold text-slate-700 block mb-1">Tanggal Diterima</label>
              <input v-model="formSuratMasuk.tanggal_diterima" type="date" required class="w-full p-2.5 rounded-xl border border-slate-200" />
            </div>
          </div>

          <div>
            <label class="font-bold text-slate-700 block mb-1">Pengirim / Instansi Asal</label>
            <input v-model="formSuratMasuk.pengirim" type="text" required placeholder="Contoh: Dinas Pendidikan Provinsi" class="w-full p-2.5 rounded-xl border border-slate-200" />
          </div>

          <div>
            <label class="font-bold text-slate-700 block mb-1">Perihal Surat</label>
            <input v-model="formSuratMasuk.perihal" type="text" required placeholder="Isi perihal pokok surat" class="w-full p-2.5 rounded-xl border border-slate-200" />
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="font-bold text-slate-700 block mb-1">Sifat Surat</label>
              <SearchableSelect
                v-model="formSuratMasuk.sifat_surat"
                :options="sifatSuratOptions"
                placeholder="Pilih Sifat Surat"
                search-placeholder="Cari sifat surat..."
              />
            </div>
            <div>
              <label class="font-bold text-slate-700 block mb-1">Kategori</label>
              <SearchableSelect
                v-model="formSuratMasuk.kategori_surat"
                :options="kategoriSuratOptions"
                placeholder="Pilih Kategori"
                search-placeholder="Cari kategori surat..."
              />
            </div>
          </div>

          <div>
            <label class="font-bold text-slate-700 block mb-1">Ringkasan / Catatan</label>
            <textarea v-model="formSuratMasuk.ringkasan_isi" rows="2" class="w-full p-2.5 rounded-xl border border-slate-200"></textarea>
          </div>

          <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
            <button type="button" @click="state.modalSuratMasuk = false" class="px-4 py-2 rounded-xl bg-slate-100 font-bold">Batal</button>
            <button type="submit" :disabled="formSuratMasuk.processing" class="px-4 py-2 rounded-xl bg-indigo-600 text-white font-bold">
              {{ formSuratMasuk.processing ? 'Menyimpan...' : 'Simpan Surat Masuk' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- 5. MODAL FORM SURAT KELUAR -->
    <div v-if="state.modalSuratKeluar" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
      <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-100 space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <h3 class="font-bold text-slate-800 text-base">
            {{ state.isEditSuratKeluar ? 'Edit Data Surat Keluar' : 'Penerbitan Surat Keluar Baru' }}
          </h3>
          <button @click="state.modalSuratKeluar = false" class="text-slate-400 hover:text-slate-600"><i class="bi bi-x-lg"></i></button>
        </div>

        <form @submit.prevent="submitSuratKeluar" class="space-y-3 text-xs">
          <div>
            <label class="font-bold text-slate-700 block mb-1">Nomor Surat Resmi</label>
            <input v-model="formSuratKeluar.nomor_surat" type="text" required class="w-full p-2.5 rounded-xl border border-slate-200" />
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="font-bold text-slate-700 block mb-1">Tujuan / Penerima</label>
              <input v-model="formSuratKeluar.tujuan_surat" type="text" required placeholder="Nama Instansi/Tujuan" class="w-full p-2.5 rounded-xl border border-slate-200" />
            </div>
            <div>
              <label class="font-bold text-slate-700 block mb-1">Tanggal Surat</label>
              <input v-model="formSuratKeluar.tanggal_surat" type="date" required class="w-full p-2.5 rounded-xl border border-slate-200" />
            </div>
          </div>

          <div>
            <label class="font-bold text-slate-700 block mb-1">Perihal Surat</label>
            <input v-model="formSuratKeluar.perihal" type="text" required placeholder="Perihal surat keluar" class="w-full p-2.5 rounded-xl border border-slate-200" />
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="font-bold text-slate-700 block mb-1">Nama Penandatangan</label>
              <input v-model="formSuratKeluar.penandatangan_nama" type="text" class="w-full p-2.5 rounded-xl border border-slate-200" />
            </div>
            <div>
              <label class="font-bold text-slate-700 block mb-1">Jabatan Penandatangan</label>
              <input v-model="formSuratKeluar.penandatangan_jabatan" type="text" class="w-full p-2.5 rounded-xl border border-slate-200" />
            </div>
          </div>

          <div>
            <label class="font-bold text-slate-700 block mb-1">Status Penerbitan</label>
            <SearchableSelect
              v-model="formSuratKeluar.status_surat"
              :options="statusSuratOptions"
              placeholder="Pilih Status Surat"
              search-placeholder="Cari status..."
            />
          </div>

          <div>
            <label class="font-bold text-slate-700 block mb-1">Ringkasan / Isi Pokok</label>
            <textarea v-model="formSuratKeluar.ringkasan_isi" rows="2" class="w-full p-2.5 rounded-xl border border-slate-200"></textarea>
          </div>

          <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
            <button type="button" @click="state.modalSuratKeluar = false" class="px-4 py-2 rounded-xl bg-slate-100 font-bold">Batal</button>
            <button type="submit" :disabled="formSuratKeluar.processing" class="px-4 py-2 rounded-xl bg-emerald-600 text-white font-bold">
              {{ formSuratKeluar.processing ? 'Menyimpan...' : 'Simpan Surat Keluar' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- 6. MODAL LEMBAR DISPOSISI PIMPINAN -->
    <div v-if="state.modalDisposisi" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
      <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-100 space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <div class="flex items-center gap-2.5">
            <div class="w-9 h-9 rounded-xl bg-indigo-100 text-indigo-700 flex items-center justify-center text-lg">
              <i class="bi bi-file-earmark-check-fill"></i>
            </div>
            <div>
              <h3 class="font-bold text-slate-800 text-base">Lembar Disposisi Digital</h3>
              <p class="text-[11px] text-slate-500">Instruksi tindak lanjut surat masuk pimpinan</p>
            </div>
          </div>
          <button @click="state.modalDisposisi = false" class="text-slate-400 hover:text-slate-600"><i class="bi bi-x-lg"></i></button>
        </div>

        <div v-if="state.selectedSuratMasuk" class="p-3.5 rounded-2xl bg-indigo-50/60 border border-indigo-100 text-xs space-y-1">
          <div class="font-bold text-indigo-950">{{ state.selectedSuratMasuk.perihal }}</div>
          <div class="text-[11px] text-indigo-800">
            Asal: {{ state.selectedSuratMasuk.pengirim }} • No: {{ state.selectedSuratMasuk.nomor_surat_asal }}
          </div>
        </div>

        <form @submit.prevent="submitDisposisi" class="space-y-3 text-xs">
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="font-bold text-slate-700 block mb-1">Pemberi Disposisi</label>
              <input v-model="formDisposisi.dari_jabatan" type="text" required class="w-full p-2.5 rounded-xl border border-slate-200" />
            </div>
            <div>
              <label class="font-bold text-slate-700 block mb-1">Diteruskan Kepada</label>
              <SearchableSelect
                v-model="formDisposisi.diteruskan_kepada"
                :options="diteruskanKepadaOptions"
                placeholder="Pilih Pejabat / Staf"
                search-placeholder="Cari pejabat tujuan..."
              />
            </div>
          </div>

          <div>
            <label class="font-bold text-slate-700 block mb-1">Instruksi Pimpinan</label>
            <textarea v-model="formDisposisi.instruksi_disposisi" rows="2" required placeholder="Instruksi / arahan yang harus ditindaklanjuti" class="w-full p-2.5 rounded-xl border border-slate-200"></textarea>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="font-bold text-slate-700 block mb-1">Batas Waktu (Opsional)</label>
              <input v-model="formDisposisi.batas_waktu_tindak_lanjut" type="date" class="w-full p-2.5 rounded-xl border border-slate-200" />
            </div>
            <div>
              <label class="font-bold text-slate-700 block mb-1">Catatan Tambahan</label>
              <input v-model="formDisposisi.catatan_tambahan" type="text" placeholder="Catatan opsional" class="w-full p-2.5 rounded-xl border border-slate-200" />
            </div>
          </div>

          <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
            <button type="button" @click="state.modalDisposisi = false" class="px-4 py-2 rounded-xl bg-slate-100 font-bold">Batal</button>
            <button type="submit" :disabled="formDisposisi.processing" class="px-4 py-2 rounded-xl bg-indigo-600 text-white font-bold">
              {{ formDisposisi.processing ? 'Menerbitkan...' : 'Terbitkan Disposisi' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>
