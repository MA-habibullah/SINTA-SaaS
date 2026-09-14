<script setup>
import { ref, computed } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link, useForm, router } from '@inertiajs/vue3'

const props = defineProps({
  members: Array,
  bukuTamuList: Object,
  statsTamu: Object,
  pengaturan: Object,
  tenants: Array,
  isSuperAdmin: Boolean,
  activeTenantId: String,
  filters: Object,
})

const activeTab = ref('anggota') // 'anggota' | 'tamu' | 'skbp' | 'kta' | 'pengaturan'
const searchQuery = ref(props.filters?.search || '')
const filterKategori = ref(props.filters?.kategori || '')
const selectedTenantId = ref(props.filters?.tenant_id || '')

const getSelectedTenantName = () => {
  if (!selectedTenantId.value) return 'Semua Sekolah (Agregat Global)'
  const found = props.tenants?.find(t => t.id === selectedTenantId.value)
  return found ? `${found.nama_sekolah} (${found.npsn})` : 'Sekolah Terpilih'
}

const applyTenantFilter = () => {
  router.get('/perpustakaan/anggota', {
    search: searchQuery.value || undefined,
    kategori: filterKategori.value || undefined,
    tenant_id: selectedTenantId.value || undefined,
  }, {
    preserveState: true,
    preserveScroll: true,
  })
}

const applySearch = () => {
  router.get('/perpustakaan/anggota', {
    search: searchQuery.value || undefined,
    kategori: filterKategori.value || undefined,
    tenant_id: selectedTenantId.value || undefined,
  }, {
    preserveState: true,
    preserveScroll: true,
  })
}

// -------------------------------------------------------------
// SURAT KETERANGAN BEBAS PERPUSTAKAAN (SKBP)
// -------------------------------------------------------------
const isModalSkbpOpen = ref(false)
const skbpLoading = ref(false)
const skbpData = ref(null)
const skbpMember = ref(null)

const periksaBebasPustaka = async (member) => {
  skbpMember.value = member
  skbpLoading.value = true
  isModalSkbpOpen.value = true

  try {
    const res = await fetch(`/perpustakaan/anggota/bebas-pustaka/${member.id}?tenant_id=${props.activeTenantId || ''}`)
    const json = await res.json()
    if (json.success) {
      skbpData.value = json
    }
  } catch (err) {
    console.error(err)
  } finally {
    skbpLoading.value = false
  }
}

const printSkbp = () => {
  window.print()
}

// -------------------------------------------------------------
// CETAK KARTU TANDA ANGGOTA (KTA) MASSAL
// -------------------------------------------------------------
const isModalKtaOpen = ref(false)
const selectedMembersForKta = ref([])

const openModalKta = (members = null) => {
  if (members) {
    selectedMembersForKta.value = Array.isArray(members) ? members : [members]
  } else {
    selectedMembersForKta.value = (props.members || []).slice(0, 8)
  }
  isModalKtaOpen.value = true
}

const printKtaWindow = () => {
  window.print()
}

// -------------------------------------------------------------
// TAMBAH ANGGOTA LUAR
// -------------------------------------------------------------
const isModalAnggotaOpen = ref(false)
const formAnggota = useForm({
  nama_lengkap: '',
  tipe_anggota: 'Umum',
  identitas_no: '',
  kelas_jurusan: 'Umum',
  jenis_kelamin: 'L',
  no_telepon: '',
  alamat: '',
})

const submitAnggota = () => {
  formAnggota.post('/perpustakaan/anggota', {
    onSuccess: () => {
      isModalAnggotaOpen.value = false
      formAnggota.reset()
    }
  })
}

const deleteAnggota = (id) => {
  if (confirm('Hapus data anggota luar ini?')) {
    router.delete(`/perpustakaan/anggota/${id}`, { preserveScroll: true })
  }
}

// -------------------------------------------------------------
// BUKU TAMU / VISITOR LOGGER
// -------------------------------------------------------------
const isModalTamuOpen = ref(false)
const formTamu = useForm({
  nama_pengunjung: '',
  tipe_pengunjung: 'Siswa',
  identitas_no: '',
  kelas_instansi: '',
  keperluan: 'Membaca / Meminjam Buku',
})

const submitTamu = () => {
  formTamu.post('/perpustakaan/buku-tamu', {
    onSuccess: () => {
      isModalTamuOpen.value = false
      formTamu.reset()
      formTamu.tipe_pengunjung = 'Siswa'
      formTamu.keperluan = 'Membaca / Meminjam Buku'
    }
  })
}

// -------------------------------------------------------------
// PENGATURAN PERPUSTAKAAN
// -------------------------------------------------------------
const formPengaturan = useForm({
  nama_perpustakaan: props.pengaturan?.nama_perpustakaan || 'Perpustakaan Digital SINTA',
  kepala_perpustakaan: props.pengaturan?.kepala_perpustakaan || 'Pustakawan Utama',
  nip_kepala: props.pengaturan?.nip_kepala || '-',
  tarif_denda_per_hari: props.pengaturan?.tarif_denda_per_hari || 1000,
  max_hari_pinjam_siswa: props.pengaturan?.max_hari_pinjam_siswa || 7,
  max_hari_pinjam_guru: props.pengaturan?.max_hari_pinjam_guru || 14,
  max_buku_pinjam_siswa: props.pengaturan?.max_buku_pinjam_siswa || 3,
  max_buku_pinjam_guru: props.pengaturan?.max_buku_pinjam_guru || 10,
  toleransi_keterlambatan: props.pengaturan?.toleransi_keterlambatan || 0,
  max_perpanjangan_siswa: props.pengaturan?.max_perpanjangan_siswa || 1,
  max_perpanjangan_guru: props.pengaturan?.max_perpanjangan_guru || 2,
  hitung_libur_denda: Boolean(props.pengaturan?.hitung_libur_denda),
  format_nomor_surat_bebas: props.pengaturan?.format_nomor_surat_bebas || '421.3/{NOMOR}/PERPUS/{TAHUN}',
  opac_aktif: Boolean(props.pengaturan?.opac_aktif ?? true),
  syarat_bebas_pustaka: props.pengaturan?.syarat_bebas_pustaka || 'Tidak memiliki pinjaman buku aktif dan tidak memiliki denda keterlambatan.',
})

const savePengaturan = () => {
  formPengaturan.post('/perpustakaan/pengaturan', {
    preserveScroll: true
  })
}
</script>

<template>
  <AppLayout title="Keanggotaan & Administrasi Perpustakaan">
    <div class="space-y-6">
      <!-- Header & Actions -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <div class="flex items-center gap-2">
            <h1 class="text-2xl font-black text-slate-800 tracking-tight">Keanggotaan & Administrasi Pemustaka</h1>
            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-extrabold bg-blue-100 text-blue-700">Auto-Federasi Terpadu</span>
          </div>
          <p class="text-xs text-slate-500 mt-1">Federasi otomatis Siswa & Guru, cetak KTA ber-barcode, presensi buku tamu, dan penerbitan Surat Bebas Pustaka (SKBP).</p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
          <button @click="openModalKta(null)" class="px-3.5 py-2 bg-slate-800 hover:bg-slate-900 text-white rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-xs">
            <i class="bi bi-person-vcard-fill"></i>
            <span>Cetak KTA Massal</span>
          </button>
          <button @click="isModalTamuOpen = true" class="px-3.5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-xs">
            <i class="bi bi-person-plus-fill"></i>
            <span>Presensi Pengunjung</span>
          </button>
          <button @click="isModalAnggotaOpen = true" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-xs shadow-blue-500/20">
            <i class="bi bi-plus-circle-fill"></i>
            <span>Anggota Luar Baru</span>
          </button>
        </div>
      </div>

      <!-- Section 2: Banner Filter Sekolah (Khusus Super Admin) -->
      <div v-if="isSuperAdmin" class="rounded-2xl shadow-xs border border-blue-100 bg-gradient-to-r from-blue-50/90 to-slate-50 border-l-4 border-l-blue-600 p-4 transition-all">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-blue-600 text-white flex items-center justify-center shadow-xs shrink-0">
              <i class="bi bi-building text-lg"></i>
            </div>
            <div>
              <div class="flex items-center gap-2">
                <span class="font-bold text-slate-800 text-sm">Filter Sekolah</span>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-blue-100 text-blue-700 border border-blue-200">
                  <i class="bi bi-funnel-fill me-1"></i> Aktif
                </span>
              </div>
              <p class="text-xs text-slate-500 mt-0.5">
                Menampilkan direktori pemustaka milik: <strong class="text-blue-700 font-bold ml-1">{{ getSelectedTenantName() }}</strong>
              </p>
            </div>
          </div>

          <div class="flex items-center gap-2">
            <label class="text-xs font-semibold text-slate-600 whitespace-nowrap hidden sm:inline">Pilih Sekolah:</label>
            <select v-model="selectedTenantId" @change="applyTenantFilter" class="text-xs rounded-xl border border-slate-200 bg-white py-2 px-3 focus:ring-2 focus:ring-blue-500 font-medium text-slate-700 min-w-[240px] shadow-2xs">
              <option value="">-- Semua Sekolah (Agregat Global) --</option>
              <option v-for="t in tenants" :key="t.id" :value="t.id">
                {{ t.nama_sekolah }} ({{ t.npsn }})
              </option>
            </select>
          </div>
        </div>
      </div>

      <!-- Quick Stats -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-3.5">
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs">
          <div class="flex items-center justify-between">
            <span class="text-xs font-bold text-slate-500">Pengunjung Hari Ini</span>
            <span class="w-8 h-8 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-sm"><i class="bi bi-people-fill"></i></span>
          </div>
          <div class="text-xl font-black text-slate-800 mt-2">{{ statsTamu?.total_hari_ini || 0 }} Orang</div>
          <div class="text-[11px] text-slate-400 mt-0.5 font-medium">Kunjungan Presensi</div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs">
          <div class="flex items-center justify-between">
            <span class="text-xs font-bold text-slate-500">Kunjungan Bulan Ini</span>
            <span class="w-8 h-8 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-sm"><i class="bi bi-calendar3"></i></span>
          </div>
          <div class="text-xl font-black text-indigo-700 mt-2">{{ statsTamu?.total_bulan_ini || 0 }} Orang</div>
          <div class="text-[11px] text-slate-400 mt-0.5 font-medium">Aktivitas Pemustaka</div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs">
          <div class="flex items-center justify-between">
            <span class="text-xs font-bold text-slate-500">Kunjungan Siswa</span>
            <span class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-sm"><i class="bi bi-backpack-fill"></i></span>
          </div>
          <div class="text-xl font-black text-emerald-600 mt-2">{{ statsTamu?.total_siswa || 0 }} Orang</div>
          <div class="text-[11px] text-emerald-600/80 mt-0.5 font-medium">Siswa Masuk Perpus</div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs">
          <div class="flex items-center justify-between">
            <span class="text-xs font-bold text-slate-500">Kunjungan Guru/Staf</span>
            <span class="w-8 h-8 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-sm"><i class="bi bi-person-workspace"></i></span>
          </div>
          <div class="text-xl font-black text-purple-700 mt-2">{{ statsTamu?.total_guru || 0 }} Orang</div>
          <div class="text-[11px] text-purple-600/80 mt-0.5 font-medium">Pendidik & Tendik</div>
        </div>
      </div>

      <!-- Modern Horizontal NavTabs Scroller (Pill Layout) -->
      <div class="bg-white rounded-2xl shadow-2xs border border-slate-200/80 p-2 relative">
        <div class="flex items-center relative">
          <button type="button" 
                  class="btn btn-sm btn-light border border-slate-200/80 rounded-xl shadow-2xs me-1.5 hidden md:flex items-center justify-center shrink-0 text-slate-600 hover:text-blue-600 hover:bg-blue-50 transition w-[34px] h-[34px] z-5" 
                  onclick="document.getElementById('navtabs-anggota')?.scrollBy({ left: -220, behavior: 'smooth' })"
                  title="Geser ke Kiri">
            <i class="bi bi-chevron-left"></i>
          </button>

          <div class="nav-tabs-wrapper grow overflow-hidden relative">
            <ul class="flex border-0 flex-nowrap overflow-x-auto whitespace-nowrap scrollable-nav-tabs gap-1.5 px-1 select-none no-scrollbar" id="navtabs-anggota" role="tablist">
              <li class="nav-item">
                <button class="border-0 font-bold px-4 py-2.5 rounded-xl text-xs transition flex items-center" 
                        :class="activeTab === 'anggota' ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'" 
                        @click="activeTab = 'anggota'">
                  <i class="bi bi-person-lines-fill me-2 text-sm"></i> 1. Direktori Anggota Pemustaka
                </button>
              </li>
              <li class="nav-item">
                <button class="border-0 font-bold px-4 py-2.5 rounded-xl text-xs transition flex items-center" 
                        :class="activeTab === 'tamu' ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'" 
                        @click="activeTab = 'tamu'">
                  <i class="bi bi-journal-text me-2 text-sm"></i> 2. Buku Tamu / Presensi Harian
                </button>
              </li>
              <li class="nav-item">
                <button class="border-0 font-bold px-4 py-2.5 rounded-xl text-xs transition flex items-center" 
                        :class="activeTab === 'pengaturan' ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'" 
                        @click="activeTab = 'pengaturan'">
                  <i class="bi bi-sliders2 me-2 text-sm"></i> 3. Kebijakan & Pengaturan Perpus
                </button>
              </li>
            </ul>
          </div>

          <button type="button" 
                  class="btn btn-sm btn-light border border-slate-200/80 rounded-xl shadow-2xs ms-1.5 hidden md:flex items-center justify-center shrink-0 text-slate-600 hover:text-blue-600 hover:bg-blue-50 transition w-[34px] h-[34px] z-5" 
                  onclick="document.getElementById('navtabs-anggota')?.scrollBy({ left: 220, behavior: 'smooth' })"
                  title="Geser ke Kanan">
            <i class="bi bi-chevron-right"></i>
          </button>
        </div>
      </div>

      <!-- TAB 1: DIREKTORI ANGGOTA -->
      <div v-if="activeTab === 'anggota'" class="space-y-4">
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs flex flex-col md:flex-row items-center justify-between gap-3">
          <div class="flex flex-wrap items-center gap-2.5 w-full md:w-auto">
            <div class="relative w-full md:w-64">
              <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400"><i class="bi bi-search"></i></span>
              <input v-model="searchQuery" @keyup.enter="applySearch" type="text" placeholder="Cari nama, NISN, NIP..." class="w-full pl-9 pr-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:ring-2 focus:ring-blue-600 focus:outline-none" />
            </div>

            <select v-model="filterKategori" @change="applySearch" class="px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:ring-2 focus:ring-blue-600 focus:outline-none">
              <option value="">Semua Kategori Anggota</option>
              <option value="Siswa">Siswa</option>
              <option value="Guru">Guru</option>
              <option value="Tendik">Tendik</option>
              <option value="Umum">Umum / Luar</option>
            </select>
          </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-2xs overflow-hidden">
          <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
              <thead>
                <tr class="bg-slate-50/80 text-slate-500 font-bold border-b border-slate-200/80">
                  <th class="py-3.5 px-4">No. Anggota (KTA)</th>
                  <th class="py-3.5 px-3">Nama Lengkap & Identitas</th>
                  <th class="py-3.5 px-3">Tipe / Peran</th>
                  <th class="py-3.5 px-3">Kelas / Unit</th>
                  <th class="py-3.5 px-3 text-center">Pinjaman Aktif</th>
                  <th class="py-3.5 px-3 text-center">Status Bebas Pustaka</th>
                  <th class="py-3.5 px-4 text-center">Layanan Khusus</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="m in (members || [])" :key="m.id" class="hover:bg-blue-50/30 transition">
                  <td class="py-3 px-4 font-mono font-bold text-blue-700">{{ m.no_anggota }}</td>
                  <td class="py-3 px-3">
                    <div class="font-extrabold text-slate-800">{{ m.nama_lengkap }}</div>
                    <div class="text-[10px] text-slate-400 font-mono">ID: {{ m.identitas_no }}</div>
                  </td>
                  <td class="py-3 px-3">
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold"
                          :class="m.tipe_anggota === 'Siswa' ? 'bg-blue-50 text-blue-700' : (m.tipe_anggota === 'Guru' ? 'bg-purple-50 text-purple-700' : 'bg-slate-100 text-slate-700')">
                      {{ m.tipe_anggota }}
                    </span>
                  </td>
                  <td class="py-3 px-3 text-slate-600">{{ m.kelas_jurusan }}</td>
                  <td class="py-3 px-3 text-center">
                    <span class="font-black text-xs" :class="m.pinjam_aktif > 0 ? 'text-amber-600 font-bold' : 'text-slate-400'">
                      {{ m.pinjam_aktif }} Buku
                    </span>
                  </td>
                  <td class="py-3 px-3 text-center">
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold border"
                          :class="m.status_bebas_pustaka ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200'">
                      {{ m.status_bebas_pustaka ? 'Clear (Bebas)' : 'Ada Tanggungan' }}
                    </span>
                  </td>
                  <td class="py-3 px-4 text-center">
                    <div class="flex items-center justify-center gap-1.5">
                      <button @click="periksaBebasPustaka(m)" class="px-2.5 py-1 bg-blue-50 hover:bg-blue-100 text-blue-700 font-bold rounded-lg transition text-[11px] flex items-center gap-1 shadow-2xs" title="Terbitkan Surat Keterangan Bebas Pustaka">
                        <i class="bi bi-file-earmark-check-fill"></i> SKBP
                      </button>
                      <button @click="openModalKta([m])" class="p-1.5 text-slate-400 hover:text-indigo-600 rounded-lg transition" title="Cetak KTA">
                        <i class="bi bi-person-vcard"></i>
                      </button>
                    </div>
                  </td>
                </tr>
                <tr v-if="!members?.length">
                  <td colspan="7" class="py-8 text-center text-slate-400">Tidak ada anggota yang cocok dengan filter pencarian.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- TAB 2: BUKU TAMU -->
      <div v-if="activeTab === 'tamu'" class="space-y-4">
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-2xs overflow-hidden">
          <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
              <thead>
                <tr class="bg-slate-50/80 text-slate-500 font-bold border-b border-slate-200/80">
                  <th class="py-3.5 px-4">Tanggal Kunjungan</th>
                  <th class="py-3.5 px-3">Nama Pengunjung</th>
                  <th class="py-3.5 px-3">Kategori</th>
                  <th class="py-3.5 px-3">Kelas / Asal Instansi</th>
                  <th class="py-3.5 px-3">Keperluan</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="t in (bukuTamuList?.data || [])" :key="t.id" class="hover:bg-blue-50/30 transition">
                  <td class="py-3 px-4 text-slate-500 font-mono">{{ t.tanggal_kunjungan }}</td>
                  <td class="py-3 px-3 font-extrabold text-slate-800">{{ t.nama_pengunjung }}</td>
                  <td class="py-3 px-3">
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-50 text-blue-700">{{ t.tipe_pengunjung }}</span>
                  </td>
                  <td class="py-3 px-3 text-slate-600">{{ t.kelas_instansi || '-' }}</td>
                  <td class="py-3 px-3 text-slate-700 font-medium">{{ t.keperluan }}</td>
                </tr>
                <tr v-if="!bukuTamuList?.data?.length">
                  <td colspan="5" class="py-8 text-center text-slate-400">Belum ada catatan pengunjung hari ini.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- TAB 3: KEBIJAKAN & PENGATURAN PERPUSTAKAAN -->
      <div v-if="activeTab === 'pengaturan'" class="space-y-4">
        <div class="bg-white rounded-3xl border border-slate-200/80 shadow-2xs p-6 max-w-3xl mx-auto text-xs">
          <h3 class="text-base font-black text-slate-800 mb-1">Pengaturan Kebijakan & Identitas Perpustakaan</h3>
          <p class="text-xs text-slate-500 mb-6">Konfigurasi aturan peminjaman, toleransi denda, dan format resmi surat keterangan.</p>

          <form @submit.prevent="savePengaturan" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="font-bold text-slate-700 block mb-1">Nama Resmi Perpustakaan *</label>
                <input v-model="formPengaturan.nama_perpustakaan" type="text" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 font-medium" />
              </div>
              <div>
                <label class="font-bold text-slate-700 block mb-1">Nama Kepala Perpustakaan</label>
                <input v-model="formPengaturan.kepala_perpustakaan" type="text" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 font-medium" />
              </div>

              <div>
                <label class="font-bold text-slate-700 block mb-1">NIP Kepala Perpustakaan</label>
                <input v-model="formPengaturan.nip_kepala" type="text" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 font-mono" />
              </div>
              <div>
                <label class="font-bold text-slate-700 block mb-1">Tarif Denda Keterlambatan / Hari (Rp) *</label>
                <input v-model="formPengaturan.tarif_denda_per_hari" type="number" min="0" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 font-bold text-blue-700" />
              </div>

              <div>
                <label class="font-bold text-slate-700 block mb-1">Toleransi Keterlambatan / Grace Period (Hari)</label>
                <input v-model="formPengaturan.toleransi_keterlambatan" type="number" min="0" placeholder="0 = langsung kena denda" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 font-medium" />
              </div>

              <div>
                <label class="font-bold text-slate-700 block mb-1">Format Nomor Surat Bebas Pustaka</label>
                <input v-model="formPengaturan.format_nomor_surat_bebas" type="text" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 font-mono text-[11px]" />
              </div>

              <div class="p-3 bg-slate-50 rounded-2xl border border-slate-100 space-y-2">
                <span class="font-bold text-slate-700 block">Aturan Durasi & Kuota Siswa</span>
                <div class="grid grid-cols-2 gap-2">
                  <div>
                    <span class="text-[10px] text-slate-400">Max Hari:</span>
                    <input v-model="formPengaturan.max_hari_pinjam_siswa" type="number" min="1" class="w-full p-2 border rounded-lg bg-white" />
                  </div>
                  <div>
                    <span class="text-[10px] text-slate-400">Max Buku:</span>
                    <input v-model="formPengaturan.max_buku_pinjam_siswa" type="number" min="1" class="w-full p-2 border rounded-lg bg-white" />
                  </div>
                </div>
              </div>

              <div class="p-3 bg-slate-50 rounded-2xl border border-slate-100 space-y-2">
                <span class="font-bold text-slate-700 block">Aturan Durasi & Kuota Guru</span>
                <div class="grid grid-cols-2 gap-2">
                  <div>
                    <span class="text-[10px] text-slate-400">Max Hari:</span>
                    <input v-model="formPengaturan.max_hari_pinjam_guru" type="number" min="1" class="w-full p-2 border rounded-lg bg-white" />
                  </div>
                  <div>
                    <span class="text-[10px] text-slate-400">Max Buku:</span>
                    <input v-model="formPengaturan.max_buku_pinjam_guru" type="number" min="1" class="w-full p-2 border rounded-lg bg-white" />
                  </div>
                </div>
              </div>

              <div class="md:col-span-2">
                <label class="font-bold text-slate-700 block mb-1">Syarat & Tata Tertib Bebas Perpustakaan</label>
                <textarea v-model="formPengaturan.syarat_bebas_pustaka" rows="2" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 font-medium"></textarea>
              </div>
            </div>

            <div class="pt-4 flex items-center justify-end gap-2 border-t border-slate-100">
              <button type="submit" :disabled="formPengaturan.processing" class="px-6 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold transition shadow-xs">
                Simpan Konfigurasi
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- MODAL POPUP: SURAT KETERANGAN BEBAS PERPUSTAKAAN (SKBP)       -->
    <!-- ============================================================== -->
    <Teleport to="body">
      <div v-if="isModalSkbpOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
        <div class="bg-white rounded-3xl border border-slate-200 shadow-2xl max-w-2xl w-full max-h-[90vh] flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-200">
          <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
            <div>
              <h2 class="text-base font-black text-slate-800">Surat Keterangan Bebas Perpustakaan (SKBP)</h2>
              <p class="text-xs text-slate-500">Pemeriksaan real-time kewajiban pinjaman dan denda pemustaka.</p>
            </div>
            <div class="flex items-center gap-2">
              <button v-if="skbpData?.is_clear" @click="printSkbp" class="px-3.5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-xs">
                <i class="bi bi-printer-fill"></i> Cetak Dokumen SKBP
              </button>
              <button @click="isModalSkbpOpen = false" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-xl hover:bg-slate-100 transition"><i class="bi bi-x-lg"></i></button>
            </div>
          </div>

          <div class="p-8 overflow-y-auto space-y-6 text-xs grow bg-white" id="printable-skbp">
            <!-- Kop Surat Resmi -->
            <div class="text-center border-b-2 border-slate-800 pb-4">
              <h2 class="font-black text-sm uppercase tracking-wider text-slate-900">{{ skbpData?.nama_perpustakaan || 'PERPUSTAKAAN DIGITAL SEKOLAH' }}</h2>
              <p class="text-[11px] text-slate-600">Unit Pelaksana Teknis Perpustakaan & Sumber Belajar</p>
              <h3 class="font-bold text-xs uppercase underline mt-3">SURAT KETERANGAN BEBAS PERPUSTAKAAN</h3>
              <p class="text-[10px] text-slate-500 font-mono mt-0.5">Nomor: {{ skbpData?.nomor_surat || '421.3/852/PERPUS/2026' }}</p>
            </div>

            <!-- Pernyataan Status -->
            <div class="space-y-3 text-slate-800">
              <p>Kepala Perpustakaan menerangkan bahwa pemustaka dengan identitas di bawah ini:</p>

              <div class="p-3.5 bg-slate-50 rounded-2xl border border-slate-200 space-y-1.5 font-medium">
                <div class="grid grid-cols-3">
                  <span class="text-slate-500">Nama Lengkap</span>
                  <span class="col-span-2 font-bold text-slate-900">: {{ skbpMember?.nama_lengkap }}</span>
                </div>
                <div class="grid grid-cols-3">
                  <span class="text-slate-500">Nomor Identitas (NISN/NIP)</span>
                  <span class="col-span-2 font-mono">: {{ skbpMember?.identitas_no }}</span>
                </div>
                <div class="grid grid-cols-3">
                  <span class="text-slate-500">Kategori / Unit</span>
                  <span class="col-span-2">: {{ skbpMember?.tipe_anggota }} - {{ skbpMember?.kelas_jurusan }}</span>
                </div>
              </div>

              <!-- Status Box -->
              <div class="p-4 rounded-2xl text-center border font-bold" :class="skbpData?.is_clear ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-rose-50 text-rose-800 border-rose-200'">
                <div class="text-sm font-black">{{ skbpData?.status_label }}</div>
                <div class="text-[11px] mt-1">
                  Pinjaman Aktif: <strong>{{ skbpData?.total_pinjam_aktif || 0 }} Buku</strong> • Tunggakan Denda: <strong>Rp {{ Number(skbpData?.total_denda_tertunggak || 0).toLocaleString('id-ID') }}</strong>
                </div>
              </div>

              <p v-if="skbpData?.is_clear">
                Telah menyelesaikan seluruh kewajiban administrasi, tidak memiliki pinjaman buku aktif, dan tidak memiliki tunggakan denda keterlambatan pada perpustakaan. Surat keterangan ini diterbitkan sebagai syarat bebas pustaka.
              </p>
              <p v-else class="text-rose-600 font-semibold">
                Pemustaka masih memiliki tanggungan pinjaman buku atau denda. Harap menyelesaikan kewajiban di meja sirkulasi sebelum surat pengesahan dicetak.
              </p>
            </div>

            <!-- Tanda Tangan & QR Code Autentikasi -->
            <div class="flex justify-between items-end pt-6 border-t border-slate-200">
              <div class="text-center font-mono text-[9px] text-slate-400">
                <div class="w-16 h-16 border border-slate-300 rounded bg-slate-50 flex items-center justify-center mx-auto text-slate-400 mb-1">
                  <i class="bi bi-qr-code text-2xl"></i>
                </div>
                <span>VERIFIKASI DIGITAL</span>
              </div>

              <div class="text-center text-xs space-y-1">
                <div>Ditetapkan pada: {{ skbpData?.tanggal_terbit || '-' }}</div>
                <div class="font-bold text-slate-800">Kepala Perpustakaan,</div>
                <div class="h-12 flex items-center justify-center text-slate-300 italic">[Tanda Tangan & Stempel]</div>
                <div class="font-bold text-slate-900 underline">{{ skbpData?.kepala_perpustakaan || 'Pustakawan Utama' }}</div>
                <div class="text-[10px] text-slate-500 font-mono">NIP: {{ skbpData?.nip_kepala || '-' }}</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- ============================================================== -->
    <!-- MODAL POPUP: CETAK KARTU TANDA ANGGOTA (KTA) MASSAL           -->
    <!-- ============================================================== -->
    <Teleport to="body">
      <div v-if="isModalKtaOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
        <div class="bg-white rounded-3xl border border-slate-200 shadow-2xl max-w-4xl w-full max-h-[90vh] flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-200">
          <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
            <div>
              <h2 class="text-base font-black text-slate-800">Preview Cetak Kartu Tanda Anggota (KTA)</h2>
              <p class="text-xs text-slate-500">Format kartu anggota perpustakaan ber-barcode standar INLISLite v3.</p>
            </div>
            <div class="flex items-center gap-2">
              <button @click="printKtaWindow" class="px-3.5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-xs">
                <i class="bi bi-printer-fill"></i> Cetak Kartu KTA
              </button>
              <button @click="isModalKtaOpen = false" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-xl hover:bg-slate-100 transition"><i class="bi bi-x-lg"></i></button>
            </div>
          </div>

          <div class="p-6 overflow-y-auto space-y-6 text-xs grow">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div v-for="m in selectedMembersForKta" :key="m.id" class="border-2 border-slate-800 rounded-2xl p-4 bg-gradient-to-r from-blue-900 to-indigo-950 text-white flex flex-col justify-between shadow-md h-52 relative overflow-hidden">
                <!-- Watermark Background -->
                <div class="absolute -right-8 -bottom-8 w-32 h-32 bg-white/5 rounded-full blur-xl pointer-events-none"></div>

                <div>
                  <div class="flex items-center justify-between border-b border-white/20 pb-2 mb-2">
                    <div>
                      <h4 class="font-extrabold text-[11px] uppercase tracking-wider text-blue-200">KARTU TANDA ANGGOTA PERPUSTAKAAN</h4>
                      <div class="text-[9px] text-slate-300">SINTA Digital Library System</div>
                    </div>
                    <span class="px-2 py-0.5 rounded text-[9px] font-black uppercase bg-blue-500/30 text-blue-200 border border-blue-400/30">
                      {{ m.tipe_anggota }}
                    </span>
                  </div>

                  <div class="flex items-center gap-3 mt-2">
                    <div class="w-12 h-16 rounded-lg bg-white/20 border border-white/30 overflow-hidden shrink-0 flex items-center justify-center text-white/50 text-xl font-black">
                      <img v-if="m.foto_url" :src="m.foto_url" alt="Foto" class="w-full h-full object-cover" />
                      <i v-else class="bi bi-person-fill"></i>
                    </div>
                    <div>
                      <h3 class="font-black text-sm text-white">{{ m.nama_lengkap }}</h3>
                      <div class="text-[10px] text-blue-200 font-mono mt-0.5">ID: {{ m.identitas_no }}</div>
                      <div class="text-[10px] text-slate-300">{{ m.kelas_jurusan }}</div>
                    </div>
                  </div>
                </div>

                <div class="flex items-center justify-between border-t border-white/20 pt-2 mt-2">
                  <div class="font-mono text-[10px] text-blue-300 font-bold tracking-widest">{{ m.no_anggota }}</div>
                  <div class="font-mono text-[9px] text-slate-400">BERLAKU: AKTIF</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Modal Tambah Anggota Luar -->
    <Teleport to="body">
      <div v-if="isModalAnggotaOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
        <div class="bg-white rounded-3xl border border-slate-200 shadow-2xl max-w-md w-full p-6 animate-in fade-in zoom-in-95 duration-200 text-xs">
          <h3 class="text-base font-black text-slate-800 mb-4">Daftarkan Anggota Luar / Umum</h3>
          <form @submit.prevent="submitAnggota" class="space-y-3.5">
            <div>
              <label class="font-bold text-slate-700 block mb-1">Nama Lengkap *</label>
              <input v-model="formAnggota.nama_lengkap" type="text" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 font-medium" />
            </div>
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="font-bold text-slate-700 block mb-1">Tipe Anggota</label>
                <select v-model="formAnggota.tipe_anggota" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200">
                  <option value="Umum">Umum</option>
                  <option value="Alumni">Alumni</option>
                  <option value="Mitra">Mitra / Peneliti</option>
                </select>
              </div>
              <div>
                <label class="font-bold text-slate-700 block mb-1">Jenis Kelamin</label>
                <select v-model="formAnggota.jenis_kelamin" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200">
                  <option value="L">Laki-Laki</option>
                  <option value="P">Perempuan</option>
                </select>
              </div>
            </div>
            <div>
              <label class="font-bold text-slate-700 block mb-1">Nomor Identitas (KTP/SIM)</label>
              <input v-model="formAnggota.identitas_no" type="text" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 font-mono" />
            </div>
            <div>
              <label class="font-bold text-slate-700 block mb-1">No. Telepon / WhatsApp</label>
              <input v-model="formAnggota.no_telepon" type="text" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200" />
            </div>
            <div>
              <label class="font-bold text-slate-700 block mb-1">Alamat Lengkap</label>
              <textarea v-model="formAnggota.alamat" rows="2" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 font-medium"></textarea>
            </div>
            <div class="pt-4 flex items-center justify-end gap-2 border-t border-slate-100">
              <button type="button" @click="isModalAnggotaOpen = false" class="px-4 py-2 rounded-xl bg-slate-100 text-slate-700 font-bold">Batal</button>
              <button type="submit" class="px-4 py-2 rounded-xl bg-blue-600 text-white font-bold">Daftarkan</button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

    <!-- Modal Buku Tamu Presensi -->
    <Teleport to="body">
      <div v-if="isModalTamuOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
        <div class="bg-white rounded-3xl border border-slate-200 shadow-2xl max-w-md w-full p-6 animate-in fade-in zoom-in-95 duration-200 text-xs">
          <h3 class="text-base font-black text-slate-800 mb-4">Presensi Kunjungan Pemustaka</h3>
          <form @submit.prevent="submitTamu" class="space-y-3.5">
            <div>
              <label class="font-bold text-slate-700 block mb-1">Nama Pengunjung *</label>
              <input v-model="formTamu.nama_pengunjung" type="text" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 font-medium" />
            </div>
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="font-bold text-slate-700 block mb-1">Tipe Pengunjung</label>
                <select v-model="formTamu.tipe_pengunjung" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200">
                  <option value="Siswa">Siswa</option>
                  <option value="Guru">Guru</option>
                  <option value="Tendik">Tendik</option>
                  <option value="Tamu">Tamu Luar</option>
                </select>
              </div>
              <div>
                <label class="font-bold text-slate-700 block mb-1">Kelas / Instansi</label>
                <input v-model="formTamu.kelas_instansi" type="text" placeholder="X-RPL / Umum" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 font-medium" />
              </div>
            </div>
            <div>
              <label class="font-bold text-slate-700 block mb-1">Tujuan / Keperluan *</label>
              <input v-model="formTamu.keperluan" type="text" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 font-medium" />
            </div>
            <div class="pt-4 flex items-center justify-end gap-2 border-t border-slate-100">
              <button type="button" @click="isModalTamuOpen = false" class="px-4 py-2 rounded-xl bg-slate-100 text-slate-700 font-bold">Batal</button>
              <button type="submit" class="px-4 py-2 rounded-xl bg-indigo-600 text-white font-bold">Catat Presensi</button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>
  </div>
</AppLayout>
</template>
