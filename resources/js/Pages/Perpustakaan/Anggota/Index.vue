<script setup>
import { ref, computed } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link, useForm, router } from '@inertiajs/vue3'

const props = defineProps({
  members: [Array, Object],
  statsAnggota: Object,
  kelasList: Array,
  bukuTamuList: Object,
  statsTamu: Object,
  pengaturan: Object,
  allMembersSelector: Array,
  tenants: Array,
  isSuperAdmin: Boolean,
  activeTenantId: String,
  filters: Object,
})

const activeTab = ref('anggota') // 'anggota' | 'tamu' | 'skbp' | 'kta' | 'pengaturan'
const searchQuery = ref(props.filters?.search || '')
const filterKategori = ref(props.filters?.kategori || '')
const filterKelas = ref(props.filters?.kelas || '')
const filterStatus = ref(props.filters?.status || '')
const selectedTenantId = ref(props.filters?.tenant_id || '')

const perPageMembers = ref(Number(props.filters?.per_page) || 15)
const perPageTamu = ref(Number(props.filters?.per_page_tamu) || 15)

const getSelectedTenantName = () => {
  if (!selectedTenantId.value) return 'Semua Sekolah (Agregat Global)'
  const found = props.tenants?.find(t => t.id === selectedTenantId.value)
  return found ? `${found.nama_sekolah} (${found.npsn})` : 'Sekolah Terpilih'
}

const applyTenantFilter = () => {
  applySearch(1)
}

const applySearch = (page = 1) => {
  router.get('/perpustakaan/anggota', {
    search: searchQuery.value || undefined,
    kategori: filterKategori.value || undefined,
    kelas: filterKelas.value || undefined,
    status: filterStatus.value || undefined,
    tenant_id: selectedTenantId.value || undefined,
    per_page: perPageMembers.value || undefined,
    page: page > 1 ? page : undefined,
    per_page_tamu: perPageTamu.value || undefined,
  }, {
    preserveState: true,
    preserveScroll: true,
  })
}

const resetFilters = () => {
  searchQuery.value = ''
  filterKategori.value = ''
  filterKelas.value = ''
  filterStatus.value = ''
  applySearch(1)
}

const applyTamuPage = (page = 1) => {
  router.get('/perpustakaan/anggota', {
    search: searchQuery.value || undefined,
    kategori: filterKategori.value || undefined,
    tenant_id: selectedTenantId.value || undefined,
    per_page: perPageMembers.value || undefined,
    per_page_tamu: perPageTamu.value || undefined,
    p_tamu: page > 1 ? page : undefined,
  }, {
    preserveState: true,
    preserveScroll: true,
  })
}

const goToPage = (url) => {
  if (!url) return
  router.visit(url, {
    preserveState: true,
    preserveScroll: true,
  })
}

const getSmartPaginationLinks = (pagination) => {
  if (!pagination?.links || pagination.links.length === 0) return []
  const rawLinks = pagination.links
  const prevLink = rawLinks[0]
  const nextLink = rawLinks[rawLinks.length - 1]
  const pageLinks = rawLinks.slice(1, -1)
  const current = pagination.current_page || 1
  const last = pagination.last_page || (pageLinks.length ? Number(pageLinks[pageLinks.length - 1].label) || 1 : 1)

  const result = []
  result.push({
    ...prevLink,
    isPrev: true,
    isNext: false,
    label: prevLink.label,
  })

  if (last <= 7) {
    pageLinks.forEach(l => {
      result.push({
        ...l,
        isPrev: false,
        isNext: false,
        label: l.label,
      })
    })
  } else {
    const pagesToShow = new Set([1, last])
    for (let p = current - 1; p <= current + 1; p++) {
      if (p >= 1 && p <= last) pagesToShow.add(p)
    }
    const sortedPages = Array.from(pagesToShow).sort((a, b) => a - b)
    let prevPage = null
    sortedPages.forEach(p => {
      if (prevPage !== null && p - prevPage > 1) {
        result.push({
          label: '...',
          url: null,
          active: false,
          isPrev: false,
          isNext: false,
        })
      }
      const foundRaw = pageLinks.find(l => l.label == p.toString())
      result.push({
        label: p.toString(),
        url: foundRaw ? foundRaw.url : null,
        active: p === current,
        isPrev: false,
        isNext: false,
      })
      prevPage = p
    })
  }

  result.push({
    ...nextLink,
    isPrev: false,
    isNext: true,
    label: nextLink.label,
  })

  return result
}

const getMembersArray = computed(() => {
  if (!props.members) return []
  return Array.isArray(props.members) ? props.members : (props.members.data || [])
})

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

const openModalKta = (membersList = null) => {
  if (membersList) {
    selectedMembersForKta.value = Array.isArray(membersList) ? membersList : [membersList]
  } else {
    selectedMembersForKta.value = getMembersArray.value.slice(0, 8)
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

// -------------------------------------------------------------
// EDIT DATA ANGGOTA PEMUSTAKA
// -------------------------------------------------------------
const isModalEditAnggotaOpen = ref(false)
const selectedMemberForEdit = ref(null)
const formEditAnggota = useForm({
  nama_lengkap: '',
  tipe_anggota: 'Siswa',
  identitas_no: '',
  kelas_jurusan: '',
  jenis_kelamin: 'L',
  no_telepon: '',
  alamat: '',
  is_active: true,
})

const openEditAnggotaModal = (member) => {
  selectedMemberForEdit.value = member
  formEditAnggota.nama_lengkap = member.nama_lengkap || ''
  formEditAnggota.tipe_anggota = member.tipe_anggota || 'Siswa'
  formEditAnggota.identitas_no = member.identitas_no || ''
  formEditAnggota.kelas_jurusan = member.kelas_jurusan || ''
  formEditAnggota.jenis_kelamin = member.jenis_kelamin || 'L'
  formEditAnggota.no_telepon = member.no_telepon || ''
  formEditAnggota.alamat = member.alamat || ''
  formEditAnggota.is_active = Boolean(member.is_active ?? true)
  isModalEditAnggotaOpen.value = true
}

const submitEditAnggota = () => {
  if (!selectedMemberForEdit.value) return
  formEditAnggota.post(`/perpustakaan/anggota/${selectedMemberForEdit.value.id}`, {
    preserveScroll: true,
    onSuccess: () => {
      isModalEditAnggotaOpen.value = false
    }
  })
}

const deleteAnggota = (id) => {
  if (confirm('Hapus data anggota ini?')) {
    router.delete(`/perpustakaan/anggota/${id}`, { preserveScroll: true })
  }
}

// -------------------------------------------------------------
// BUKU TAMU / VISITOR LOGGER & DATABASE ANGGOTA SELECTOR
// -------------------------------------------------------------
const isModalTamuOpen = ref(false)
const memberSearchKeyword = ref('')
const isMemberDropdownOpen = ref(false)
const selectedMemberTamu = ref(null)
const isManualGuestMode = ref(false)

const formTamu = useForm({
  nama_pengunjung: '',
  tipe_pengunjung: 'Siswa',
  identitas_no: '',
  kelas_instansi: '',
  keperluan: 'Membaca / Meminjam Buku',
})

const filteredMembersForTamu = computed(() => {
  const list = (props.allMembersSelector && props.allMembersSelector.length > 0)
    ? props.allMembersSelector
    : (Array.isArray(props.members) ? props.members : (props.members?.data || []))

  if (!memberSearchKeyword.value || memberSearchKeyword.value.trim() === '') {
    return list.slice(0, 15)
  }
  const q = memberSearchKeyword.value.toLowerCase().trim()
  return list.filter(m => 
    (m.nama_lengkap && m.nama_lengkap.toLowerCase().includes(q)) ||
    (m.identitas_no && m.identitas_no.toLowerCase().includes(q)) ||
    (m.no_anggota && m.no_anggota.toLowerCase().includes(q)) ||
    (m.kelas_jurusan && m.kelas_jurusan.toLowerCase().includes(q))
  ).slice(0, 20)
})

const selectMemberForTamu = (m) => {
  selectedMemberTamu.value = m
  formTamu.nama_pengunjung = m.nama_lengkap
  formTamu.tipe_pengunjung = m.tipe_anggota || 'Siswa'
  formTamu.identitas_no = m.identitas_no || ''
  formTamu.kelas_instansi = m.kelas_jurusan || 'Umum'
  memberSearchKeyword.value = m.nama_lengkap
  isMemberDropdownOpen.value = false
  isManualGuestMode.value = false
}

const clearMemberSelection = () => {
  selectedMemberTamu.value = null
  memberSearchKeyword.value = ''
  formTamu.nama_pengunjung = ''
  formTamu.tipe_pengunjung = 'Siswa'
  formTamu.identitas_no = ''
  formTamu.kelas_instansi = ''
  isManualGuestMode.value = false
}

const toggleManualGuestMode = () => {
  isManualGuestMode.value = !isManualGuestMode.value
  selectedMemberTamu.value = null
  if (isManualGuestMode.value) {
    formTamu.tipe_pengunjung = 'Tamu'
    formTamu.kelas_instansi = 'Umum / Tamu Luar'
  } else {
    formTamu.tipe_pengunjung = 'Siswa'
    formTamu.kelas_instansi = ''
  }
}

const openModalTamu = () => {
  clearMemberSelection()
  formTamu.keperluan = 'Membaca / Meminjam Buku'
  isModalTamuOpen.value = true
}

const submitTamu = () => {
  formTamu.post('/perpustakaan/buku-tamu', {
    onSuccess: () => {
      isModalTamuOpen.value = false
      clearMemberSelection()
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

// -------------------------------------------------------------
// SINKRONISASI / TARIK DATA ANGGOTA DARI MASTER (SISWA & GURU)
// -------------------------------------------------------------
const isModalSyncOpen = ref(false)
const isSyncing = ref(false)
const formSync = useForm({
  tenant_id: selectedTenantId.value || props.activeTenantId,
  scope: 'all', // 'all' | 'siswa' | 'guru'
})

const openModalSync = () => {
  formSync.tenant_id = selectedTenantId.value || props.activeTenantId
  formSync.scope = 'all'
  isModalSyncOpen.value = true
}

const executeSyncMaster = () => {
  isSyncing.value = true
  formSync.post('/perpustakaan/anggota/sync-master', {
    preserveScroll: true,
    onSuccess: () => {
      isModalSyncOpen.value = false
      isSyncing.value = false
    },
    onError: () => {
      isSyncing.value = false
    },
    onFinish: () => {
      isSyncing.value = false
    }
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
          <button @click="openModalSync" class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-xs shadow-emerald-600/20">
            <i class="bi bi-cloud-arrow-down-fill"></i>
            <span>Tarik Data Anggota</span>
          </button>
          <button @click="openModalKta(null)" class="px-3.5 py-2 bg-slate-800 hover:bg-slate-900 text-white rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-xs">
            <i class="bi bi-person-vcard-fill"></i>
            <span>Cetak KTA Massal</span>
          </button>
          <button @click="openModalTamu" class="px-3.5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-xs">
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

      <!-- Quick Stats Pemustaka & Aktivitas -->
      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3.5">
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs">
          <div class="flex items-center justify-between">
            <span class="text-xs font-bold text-slate-500">Siswa Aktif</span>
            <span class="w-8 h-8 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-sm"><i class="bi bi-mortarboard-fill"></i></span>
          </div>
          <div class="text-xl font-black text-blue-700 mt-2">{{ statsAnggota?.total_siswa_aktif || 0 }} Siswa</div>
          <div class="text-[11px] text-blue-600/80 mt-0.5 font-medium">Pemustaka Peserta Didik</div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs">
          <div class="flex items-center justify-between">
            <span class="text-xs font-bold text-slate-500">Guru & Tendik Aktif</span>
            <span class="w-8 h-8 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-sm"><i class="bi bi-person-workspace"></i></span>
          </div>
          <div class="text-xl font-black text-purple-700 mt-2">{{ statsAnggota?.total_guru_aktif || 0 }} Guru/Staf</div>
          <div class="text-[11px] text-purple-600/80 mt-0.5 font-medium">Pendidik & Kependidikan</div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs">
          <div class="flex items-center justify-between">
            <span class="text-xs font-bold text-slate-500">Alumni / Lulus</span>
            <span class="w-8 h-8 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-sm"><i class="bi bi-award-fill"></i></span>
          </div>
          <div class="text-xl font-black text-indigo-700 mt-2">{{ statsAnggota?.total_alumni || 0 }} Orang</div>
          <div class="text-[11px] text-indigo-600/80 mt-0.5 font-medium">Siswa Lulus & Purna</div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs">
          <div class="flex items-center justify-between">
            <span class="text-xs font-bold text-slate-500">Total Terdaftar</span>
            <span class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-sm"><i class="bi bi-people-fill"></i></span>
          </div>
          <div class="text-xl font-black text-emerald-700 mt-2">{{ statsAnggota?.total_anggota || 0 }} Anggota</div>
          <div class="text-[11px] text-emerald-600/80 mt-0.5 font-medium">Direktori Pemustaka</div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs col-span-2 sm:col-span-1">
          <div class="flex items-center justify-between">
            <span class="text-xs font-bold text-slate-500">Pengunjung Hari Ini</span>
            <span class="w-8 h-8 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-sm"><i class="bi bi-journal-check"></i></span>
          </div>
          <div class="text-xl font-black text-amber-600 mt-2">{{ statsTamu?.total_hari_ini || 0 }} Kunjungan</div>
          <div class="text-[11px] text-amber-600/80 mt-0.5 font-medium">Presensi Buku Tamu</div>
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
            <!-- Input Cari -->
            <div class="relative w-full md:w-56">
              <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400"><i class="bi bi-search"></i></span>
              <input v-model="searchQuery" @keyup.enter="applySearch(1)" type="text" placeholder="Cari nama, NISN, NIP..." class="w-full pl-9 pr-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:ring-2 focus:ring-blue-600 focus:outline-none" />
            </div>

            <!-- Filter Kategori / Tipe -->
            <select v-model="filterKategori" @change="applySearch(1)" class="px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:ring-2 focus:ring-blue-600 focus:outline-none">
              <option value="">Semua Kategori</option>
              <option value="Siswa">Siswa</option>
              <option value="Guru">Guru</option>
              <option value="Tendik">Tendik</option>
              <option value="Alumni">Alumni / Lulus</option>
              <option value="Umum">Umum / Luar</option>
            </select>

            <!-- Filter Kelas / Rombel -->
            <select v-model="filterKelas" @change="applySearch(1)" class="px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:ring-2 focus:ring-blue-600 focus:outline-none max-w-[180px]">
              <option value="">Semua Kelas / Rombel</option>
              <option v-for="k in (kelasList || [])" :key="k" :value="k">{{ k }}</option>
            </select>

            <!-- Filter Status Keanggotaan -->
            <select v-model="filterStatus" @change="applySearch(1)" class="px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:ring-2 focus:ring-blue-600 focus:outline-none">
              <option value="">Semua Status</option>
              <option value="aktif">Aktif</option>
              <option value="non-aktif">Non-Aktif / Terblokir</option>
              <option value="alumni">Alumni / Lulus</option>
            </select>

            <!-- Tombol Reset Filter -->
            <button v-if="searchQuery || filterKategori || filterKelas || filterStatus" 
                    @click="resetFilters" 
                    class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-xs font-bold transition flex items-center gap-1">
              <i class="bi bi-x-circle"></i> Reset Filter
            </button>
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
                  <th class="py-3.5 px-3 text-center">Status</th>
                  <th class="py-3.5 px-3 text-center">Pinjaman Aktif</th>
                  <th class="py-3.5 px-3 text-center">Status Bebas Pustaka</th>
                  <th class="py-3.5 px-4 text-center">Layanan & Aksi</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="m in getMembersArray" :key="m.id" class="hover:bg-blue-50/30 transition">
                  <td class="py-3 px-4 font-mono font-bold text-blue-700">{{ m.no_anggota }}</td>
                  <td class="py-3 px-3">
                    <div class="font-extrabold text-slate-800">{{ m.nama_lengkap }}</div>
                    <div class="text-[10px] text-slate-400 font-mono">ID: {{ m.identitas_no }}</div>
                  </td>
                  <td class="py-3 px-3">
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold"
                          :class="m.tipe_anggota === 'Siswa' ? 'bg-blue-50 text-blue-700' : (m.tipe_anggota === 'Guru' ? 'bg-purple-50 text-purple-700' : (m.tipe_anggota === 'Alumni' ? 'bg-indigo-50 text-indigo-700' : 'bg-slate-100 text-slate-700'))">
                      {{ m.tipe_anggota }}
                    </span>
                  </td>
                  <td class="py-3 px-3 text-slate-600">{{ m.kelas_jurusan }}</td>
                  <td class="py-3 px-3 text-center">
                    <span v-if="m.tipe_anggota === 'Alumni'" class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-indigo-50 text-indigo-700 border border-indigo-200">
                      Alumni / Lulus
                    </span>
                    <span v-else-if="m.is_active" class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-50 text-emerald-700 border border-emerald-200">
                      Aktif
                    </span>
                    <span v-else class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-rose-50 text-rose-700 border border-rose-200">
                      Non-Aktif
                    </span>
                  </td>
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
                      <button @click="openEditAnggotaModal(m)" class="p-1.5 text-slate-400 hover:text-blue-600 rounded-lg transition" title="Edit Profil Anggota">
                        <i class="bi bi-pencil-square"></i>
                      </button>
                      <button @click="deleteAnggota(m.id)" class="p-1.5 text-slate-400 hover:text-rose-600 rounded-lg transition" title="Hapus Anggota">
                        <i class="bi bi-trash"></i>
                      </button>
                    </div>
                  </td>
                </tr>
                <tr v-if="!getMembersArray.length">
                  <td colspan="8" class="py-8 text-center text-slate-400">Tidak ada anggota yang cocok dengan filter pencarian.</td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination Footer Tab Anggota -->
          <div v-if="members?.total || getMembersArray.length" class="px-4 py-3 bg-slate-50/50 border-t border-slate-200/80 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-500">
            <div class="flex items-center gap-2">
              <span>Tampilkan</span>
              <select v-model="perPageMembers" @change="applySearch(1)" class="h-8 px-2 rounded-lg border border-slate-200 bg-white text-xs font-bold text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                <option :value="5">5</option>
                <option :value="10">10</option>
                <option :value="15">15</option>
                <option :value="25">25</option>
                <option :value="50">50</option>
                <option :value="100">100</option>
              </select>
              <span>baris per halaman</span>
              <span class="text-slate-300 hidden sm:inline">|</span>
              <span class="whitespace-nowrap">
                Menampilkan <span class="font-bold text-slate-800">{{ members.from || 1 }}</span> s.d. <span class="font-bold text-slate-800">{{ members.to || (members.total || getMembersArray.length) }}</span> dari <span class="font-bold text-slate-800">{{ members.total || getMembersArray.length }}</span> anggota
              </span>
            </div>

            <!-- Smart Windowing Pagination Links -->
            <div v-if="members.links && members.links.length > 3" class="flex items-center gap-1 shrink-0 flex-wrap">
              <template v-for="(link, i) in getSmartPaginationLinks(members)" :key="i">
                <button v-if="link.url && !link.active" 
                        type="button"
                        @click="goToPage(link.url)"
                        class="min-w-[32px] h-8 px-2.5 rounded-xl text-xs font-bold transition flex items-center justify-center bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 shadow-2xs"
                        :title="link.isPrev ? 'Halaman Sebelumnya' : (link.isNext ? 'Halaman Berikutnya' : 'Halaman ' + link.label)">
                  <i v-if="link.isPrev" class="bi bi-chevron-left text-xs"></i>
                  <i v-else-if="link.isNext" class="bi bi-chevron-right text-xs"></i>
                  <span v-else>{{ link.label }}</span>
                </button>
                <span v-else-if="link.active" 
                      class="min-w-[32px] h-8 px-2.5 rounded-xl text-xs font-bold flex items-center justify-center bg-blue-600 text-white shadow-xs">
                  <i v-if="link.isPrev" class="bi bi-chevron-left text-xs"></i>
                  <i v-else-if="link.isNext" class="bi bi-chevron-right text-xs"></i>
                  <span v-else>{{ link.label }}</span>
                </span>
                <span v-else 
                      class="min-w-[32px] h-8 px-2 text-xs font-bold flex items-center justify-center text-slate-400">
                  <i v-if="link.isPrev" class="bi bi-chevron-left text-xs text-slate-300"></i>
                  <i v-else-if="link.isNext" class="bi bi-chevron-right text-xs text-slate-300"></i>
                  <span v-else>{{ link.label }}</span>
                </span>
              </template>
            </div>
          </div>
        </div>
      </div>

      <!-- TAB 2: BUKU TAMU -->
      <div v-if="activeTab === 'tamu'" class="space-y-4">
        <!-- Action Bar Buku Tamu -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs flex flex-col sm:flex-row items-center justify-between gap-3">
          <div>
            <h3 class="text-sm font-bold text-slate-800">Catatan Presensi Kunjungan Pemustaka</h3>
            <p class="text-xs text-slate-500">Log kehadiran harian siswa, guru, staf, dan tamu umum di perpustakaan.</p>
          </div>
          <button @click="openModalTamu" class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition flex items-center gap-2 shadow-xs shrink-0">
            <i class="bi bi-person-plus-fill text-sm"></i>
            <span>+ Catat Presensi Pengunjung</span>
          </button>
        </div>

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

          <!-- Pagination Footer Tab Buku Tamu -->
          <div v-if="bukuTamuList?.total" class="px-4 py-3 bg-slate-50/50 border-t border-slate-200/80 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-500">
            <div class="flex items-center gap-2">
              <span>Tampilkan</span>
              <select v-model="perPageTamu" @change="applyTamuPage(1)" class="h-8 px-2 rounded-lg border border-slate-200 bg-white text-xs font-bold text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                <option :value="5">5</option>
                <option :value="10">10</option>
                <option :value="15">15</option>
                <option :value="25">25</option>
                <option :value="50">50</option>
                <option :value="100">100</option>
              </select>
              <span>baris per halaman</span>
              <span class="text-slate-300 hidden sm:inline">|</span>
              <span class="whitespace-nowrap">
                Menampilkan <span class="font-bold text-slate-800">{{ bukuTamuList.from || 1 }}</span> s.d. <span class="font-bold text-slate-800">{{ bukuTamuList.to || bukuTamuList.total }}</span> dari <span class="font-bold text-slate-800">{{ bukuTamuList.total }}</span> kunjungan
              </span>
            </div>

            <!-- Smart Windowing Pagination Links -->
            <div v-if="bukuTamuList.links && bukuTamuList.links.length > 3" class="flex items-center gap-1 shrink-0 flex-wrap">
              <template v-for="(link, i) in getSmartPaginationLinks(bukuTamuList)" :key="i">
                <button v-if="link.url && !link.active" 
                        type="button"
                        @click="goToPage(link.url)"
                        class="min-w-[32px] h-8 px-2.5 rounded-xl text-xs font-bold transition flex items-center justify-center bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 shadow-2xs"
                        :title="link.isPrev ? 'Halaman Sebelumnya' : (link.isNext ? 'Halaman Berikutnya' : 'Halaman ' + link.label)">
                  <i v-if="link.isPrev" class="bi bi-chevron-left text-xs"></i>
                  <i v-else-if="link.isNext" class="bi bi-chevron-right text-xs"></i>
                  <span v-else>{{ link.label }}</span>
                </button>
                <span v-else-if="link.active" 
                      class="min-w-[32px] h-8 px-2.5 rounded-xl text-xs font-bold flex items-center justify-center bg-blue-600 text-white shadow-xs">
                  <i v-if="link.isPrev" class="bi bi-chevron-left text-xs"></i>
                  <i v-else-if="link.isNext" class="bi bi-chevron-right text-xs"></i>
                  <span v-else>{{ link.label }}</span>
                </span>
                <span v-else 
                      class="min-w-[32px] h-8 px-2 text-xs font-bold flex items-center justify-center text-slate-400">
                  <i v-if="link.isPrev" class="bi bi-chevron-left text-xs text-slate-300"></i>
                  <i v-else-if="link.isNext" class="bi bi-chevron-right text-xs text-slate-300"></i>
                  <span v-else>{{ link.label }}</span>
                </span>
              </template>
            </div>
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

    <!-- Modal Buku Tamu Presensi (Live Autocomplete Database Anggota) -->
    <Teleport to="body">
      <div v-if="isModalTamuOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
        <div class="bg-white rounded-3xl border border-slate-200 shadow-2xl max-w-lg w-full max-h-[90vh] flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-200 text-xs">
          <!-- Header Modal -->
          <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-lg font-bold shrink-0">
                <i class="bi bi-journal-check"></i>
              </div>
              <div>
                <h3 class="text-base font-black text-slate-800">Presensi Kunjungan Pemustaka</h3>
                <p class="text-xs text-slate-500">Catat kehadiran pemustaka dari database anggota atau tamu umum.</p>
              </div>
            </div>
            <button @click="isModalTamuOpen = false" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-xl hover:bg-slate-100 transition">
              <i class="bi bi-x-lg"></i>
            </button>
          </div>

          <!-- Body Form -->
          <form @submit.prevent="submitTamu" class="p-6 overflow-y-auto space-y-4 grow">
            <!-- Mode Switcher -->
            <div class="flex items-center justify-between p-1 bg-slate-100 rounded-xl">
              <button type="button" 
                      @click="isManualGuestMode = false" 
                      class="flex-1 py-1.5 px-3 rounded-lg font-bold text-xs transition flex items-center justify-center gap-1.5"
                      :class="!isManualGuestMode ? 'bg-white text-indigo-700 shadow-2xs' : 'text-slate-600 hover:text-slate-800'">
                <i class="bi bi-person-bounding-box"></i>
                <span>Database Anggota</span>
              </button>
              <button type="button" 
                      @click="toggleManualGuestMode" 
                      class="flex-1 py-1.5 px-3 rounded-lg font-bold text-xs transition flex items-center justify-center gap-1.5"
                      :class="isManualGuestMode ? 'bg-white text-indigo-700 shadow-2xs' : 'text-slate-600 hover:text-slate-800'">
                <i class="bi bi-pencil-square"></i>
                <span>Ketik Manual (Tamu Luar)</span>
              </button>
            </div>

            <!-- Mode 1: Ambil dari Database Anggota -->
            <div v-if="!isManualGuestMode" class="space-y-2">
              <label class="font-bold text-slate-700 block">
                Cari & Pilih Anggota Perpustakaan *
              </label>

              <!-- Card Info Jika Sudah Terpilih -->
              <div v-if="selectedMemberTamu" class="p-3.5 bg-indigo-50/80 border border-indigo-200 rounded-2xl flex items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 rounded-xl bg-indigo-600 text-white flex items-center justify-center font-black text-sm shrink-0">
                    {{ selectedMemberTamu.nama_lengkap ? selectedMemberTamu.nama_lengkap.charAt(0).toUpperCase() : 'A' }}
                  </div>
                  <div>
                    <div class="font-black text-slate-900 text-sm">{{ selectedMemberTamu.nama_lengkap }}</div>
                    <div class="flex items-center gap-2 text-[11px] text-slate-500 mt-0.5">
                      <span class="font-mono">ID: {{ selectedMemberTamu.identitas_no || selectedMemberTamu.no_anggota }}</span>
                      <span>•</span>
                      <span class="px-1.5 py-0.2 rounded bg-indigo-100 text-indigo-800 font-bold text-[10px]">{{ selectedMemberTamu.tipe_anggota }}</span>
                      <span>•</span>
                      <span class="text-slate-600 font-medium">{{ selectedMemberTamu.kelas_jurusan || '-' }}</span>
                    </div>
                  </div>
                </div>
                <button type="button" @click="clearMemberSelection" class="px-2.5 py-1 text-[11px] font-bold bg-white text-rose-600 border border-rose-200 hover:bg-rose-50 rounded-lg transition shrink-0">
                  Ganti
                </button>
              </div>

              <!-- Search Bar & Dropdown Auto-suggest Jika Belum Terpilih -->
              <div v-else class="relative">
                <div class="relative">
                  <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                    <i class="bi bi-search"></i>
                  </span>
                  <input v-model="memberSearchKeyword" 
                         @focus="isMemberDropdownOpen = true"
                         type="text" 
                         placeholder="Ketik nama siswa, guru, NISN, atau kelas..." 
                         class="w-full pl-9 pr-8 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:ring-2 focus:ring-indigo-600 focus:bg-white focus:outline-none" />
                  <button v-if="memberSearchKeyword" 
                          type="button" 
                          @click="memberSearchKeyword = ''" 
                          class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-slate-400 hover:text-slate-600">
                    <i class="bi bi-x-circle-fill"></i>
                  </button>
                </div>

                <!-- Dropdown List Hasil Pencarian -->
                <div v-if="isMemberDropdownOpen && filteredMembersForTamu.length > 0" 
                     class="absolute left-0 right-0 top-full mt-1.5 bg-white border border-slate-200 rounded-2xl shadow-xl z-20 max-h-56 overflow-y-auto divide-y divide-slate-100">
                  <div v-for="m in filteredMembersForTamu" 
                       :key="m.id" 
                       @click="selectMemberForTamu(m)"
                       class="p-2.5 hover:bg-indigo-50/60 cursor-pointer transition flex items-center justify-between gap-2">
                    <div class="flex items-center gap-2.5 min-w-0">
                      <div class="w-7 h-7 rounded-lg bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold text-xs shrink-0">
                        {{ m.nama_lengkap ? m.nama_lengkap.charAt(0).toUpperCase() : 'A' }}
                      </div>
                      <div class="truncate">
                        <div class="font-bold text-slate-800 text-xs truncate">{{ m.nama_lengkap }}</div>
                        <div class="text-[10px] text-slate-400 font-mono flex items-center gap-1.5">
                          <span>NISN/ID: {{ m.identitas_no || '-' }}</span>
                          <span>•</span>
                          <span>{{ m.kelas_jurusan || '-' }}</span>
                        </div>
                      </div>
                    </div>
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold shrink-0"
                          :class="m.tipe_anggota === 'Siswa' ? 'bg-blue-50 text-blue-700' : (m.tipe_anggota === 'Guru' ? 'bg-purple-50 text-purple-700' : 'bg-slate-100 text-slate-700')">
                      {{ m.tipe_anggota }}
                    </span>
                  </div>
                </div>

                <div v-if="isMemberDropdownOpen && memberSearchKeyword && filteredMembersForTamu.length === 0" 
                     class="absolute left-0 right-0 top-full mt-1.5 bg-white border border-slate-200 rounded-2xl shadow-xl z-20 p-4 text-center text-slate-400">
                  <p>Tidak ditemukan anggota dengan kata kunci "<strong>{{ memberSearchKeyword }}</strong>".</p>
                  <button type="button" @click="toggleManualGuestMode" class="mt-2 text-indigo-600 hover:underline font-bold text-xs">
                    Gunakan Mode Ketik Manual &rarr;
                  </button>
                </div>
              </div>
            </div>

            <!-- Mode 2: Ketik Manual (Tamu Luar) -->
            <div v-else class="space-y-1">
              <label class="font-bold text-slate-700 block">Nama Lengkap Pengunjung *</label>
              <input v-model="formTamu.nama_pengunjung" 
                     type="text" 
                     required 
                     placeholder="Nama lengkap tamu / instansi luar..." 
                     class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 font-medium focus:ring-2 focus:ring-indigo-600 focus:outline-none" />
            </div>

            <!-- Detail Tipe & Kelas / Instansi -->
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="font-bold text-slate-700 block mb-1">Tipe Pemustaka</label>
                <select v-model="formTamu.tipe_pengunjung" 
                        :disabled="!isManualGuestMode && selectedMemberTamu !== null"
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50 font-medium disabled:opacity-80">
                  <option value="Siswa">Siswa</option>
                  <option value="Guru">Guru</option>
                  <option value="Tendik">Tendik / Staf</option>
                  <option value="Alumni">Alumni</option>
                  <option value="Tamu">Tamu Luar</option>
                </select>
              </div>
              <div>
                <label class="font-bold text-slate-700 block mb-1">Kelas / Unit / Instansi</label>
                <input v-model="formTamu.kelas_instansi" 
                       :readonly="!isManualGuestMode && selectedMemberTamu !== null"
                       type="text" 
                       placeholder="Contoh: X-RPL / Umum" 
                       class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 font-medium focus:ring-2 focus:ring-indigo-600 focus:outline-none bg-slate-50" />
              </div>
            </div>

            <!-- Nomor Identitas (Opsional / Terisi Otomatis) -->
            <div>
              <label class="font-bold text-slate-700 block mb-1">Nomor Identitas (NISN / NIP / KTP)</label>
              <input v-model="formTamu.identitas_no" 
                     :readonly="!isManualGuestMode && selectedMemberTamu !== null"
                     type="text" 
                     placeholder="Nomor identitas (opsional)" 
                     class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 font-mono text-slate-600 bg-slate-50 focus:ring-2 focus:ring-indigo-600 focus:outline-none" />
            </div>

            <!-- Tujuan / Keperluan -->
            <div class="space-y-1.5">
              <label class="font-bold text-slate-700 block">Tujuan / Keperluan Kunjungan *</label>
              <input v-model="formTamu.keperluan" 
                     type="text" 
                     required 
                     class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 font-medium focus:ring-2 focus:ring-indigo-600 focus:outline-none" />
              
              <!-- Quick Shortcut Pills -->
              <div class="flex flex-wrap gap-1.5 pt-1">
                <button v-for="tag in ['Membaca / Meminjam Buku', 'Belajar Mandiri / Tugas', 'Akses Komputer & Internet', 'Riset Karya Tulis', 'Pengembalian Buku']"
                        :key="tag"
                        type="button"
                        @click="formTamu.keperluan = tag"
                        class="px-2.5 py-1 rounded-lg text-[10px] font-bold border transition"
                        :class="formTamu.keperluan === tag ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-slate-50 text-slate-600 border-slate-200 hover:bg-slate-100'">
                  {{ tag }}
                </button>
              </div>
            </div>

            <!-- Tombol Aksi -->
            <div class="pt-4 flex items-center justify-end gap-2 border-t border-slate-100">
              <button type="button" @click="isModalTamuOpen = false" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold transition">
                Batal
              </button>
              <button type="submit" 
                      :disabled="formTamu.processing || (!formTamu.nama_pengunjung)"
                      class="px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white font-bold transition flex items-center gap-1.5 shadow-xs">
                <i class="bi bi-check-circle-fill"></i>
                <span>Catat Presensi</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

    <!-- Modal Tarik & Sinkronisasi Data Anggota Master -->
    <Teleport to="body">
      <div v-if="isModalSyncOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
        <div class="bg-white rounded-3xl border border-slate-200 shadow-2xl max-w-lg w-full p-6 animate-in fade-in zoom-in-95 duration-200 text-xs">
          <div class="flex items-center gap-3 mb-3">
            <div class="w-10 h-10 rounded-2xl bg-emerald-100 text-emerald-700 flex items-center justify-center text-xl font-bold shrink-0">
              <i class="bi bi-cloud-arrow-down-fill"></i>
            </div>
            <div>
              <h3 class="text-base font-black text-slate-800">Tarik & Sinkronisasi Anggota</h3>
              <p class="text-xs text-slate-500">Impor dan perbarui data anggota dari Master Pengguna & Siswa.</p>
            </div>
          </div>

          <div class="p-3.5 bg-emerald-50/70 border border-emerald-200 rounded-2xl mb-4 space-y-1.5">
            <div class="flex items-center gap-2 text-emerald-900 font-bold">
              <i class="bi bi-shield-check text-base text-emerald-600"></i>
              <span>Proteksi Anti-Duplikasi Otomatis</span>
            </div>
            <p class="text-[11px] text-emerald-700 leading-relaxed">
              Sistem akan memvalidasi NISN, NIP, dan ID Pengguna. Data yang belum ada akan didaftarkan otomatis, dan data yang sudah ada akan diperbarui tanpa membuat duplikat ganda.
            </p>
          </div>

          <form @submit.prevent="executeSyncMaster" class="space-y-4">
            <!-- Filter Target Sekolah jika Super Admin -->
            <div v-if="isSuperAdmin && (tenants || []).length > 0">
              <label class="font-bold text-slate-700 block mb-1">Target Sekolah / Tenant *</label>
              <select v-model="formSync.tenant_id" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50 font-medium">
                <option v-for="t in tenants" :key="t.id" :value="t.id">{{ t.nama_sekolah }} (NPSN: {{ t.npsn }})</option>
              </select>
            </div>

            <!-- Cakupan Sinkronisasi -->
            <div>
              <label class="font-bold text-slate-700 block mb-2">Cakupan Data yang Ditarik:</label>
              <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                <label class="p-3 rounded-xl border cursor-pointer flex flex-col items-center text-center transition"
                       :class="formSync.scope === 'all' ? 'bg-blue-50/70 border-blue-500 text-blue-800 shadow-2xs' : 'bg-slate-50 border-slate-200 text-slate-600 hover:bg-slate-100'">
                  <input type="radio" v-model="formSync.scope" value="all" class="sr-only" />
                  <i class="bi bi-people-fill text-lg mb-1" :class="formSync.scope === 'all' ? 'text-blue-600' : 'text-slate-400'"></i>
                  <span class="font-bold text-xs">Semua Data</span>
                  <span class="text-[10px] text-slate-400">Siswa & Guru/Staf</span>
                </label>

                <label class="p-3 rounded-xl border cursor-pointer flex flex-col items-center text-center transition"
                       :class="formSync.scope === 'siswa' ? 'bg-blue-50/70 border-blue-500 text-blue-800 shadow-2xs' : 'bg-slate-50 border-slate-200 text-slate-600 hover:bg-slate-100'">
                  <input type="radio" v-model="formSync.scope" value="siswa" class="sr-only" />
                  <i class="bi bi-mortarboard-fill text-lg mb-1" :class="formSync.scope === 'siswa' ? 'text-blue-600' : 'text-slate-400'"></i>
                  <span class="font-bold text-xs">Hanya Siswa</span>
                  <span class="text-[10px] text-slate-400">Database Siswa</span>
                </label>

                <label class="p-3 rounded-xl border cursor-pointer flex flex-col items-center text-center transition"
                       :class="formSync.scope === 'guru' ? 'bg-blue-50/70 border-blue-500 text-blue-800 shadow-2xs' : 'bg-slate-50 border-slate-200 text-slate-600 hover:bg-slate-100'">
                  <input type="radio" v-model="formSync.scope" value="guru" class="sr-only" />
                  <i class="bi bi-person-workspace text-lg mb-1" :class="formSync.scope === 'guru' ? 'text-blue-600' : 'text-slate-400'"></i>
                  <span class="font-bold text-xs">Hanya Guru/GTK</span>
                  <span class="text-[10px] text-slate-400">Master Pengguna</span>
                </label>
              </div>
            </div>

            <div class="pt-4 flex items-center justify-end gap-2 border-t border-slate-100">
              <button type="button" @click="isModalSyncOpen = false" :disabled="isSyncing" class="px-4 py-2.5 rounded-xl bg-slate-100 text-slate-700 font-bold hover:bg-slate-200 transition disabled:opacity-50">
                Batal
              </button>
              <button type="submit" :disabled="isSyncing" class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-black transition flex items-center gap-2 shadow-xs shadow-emerald-600/20 disabled:opacity-50">
                <i v-if="isSyncing" class="bi bi-arrow-repeat animate-spin"></i>
                <i v-else class="bi bi-cloud-arrow-down-fill"></i>
                <span>{{ isSyncing ? 'Sedang Memproses...' : 'Mulai Tarik / Update Data' }}</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

    <!-- Modal Edit Data Anggota -->
    <Teleport to="body">
      <div v-if="isModalEditAnggotaOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
        <div class="bg-white rounded-3xl border border-slate-200 shadow-2xl max-w-lg w-full p-6 animate-in fade-in zoom-in-95 duration-200 text-xs">
          <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
            <div class="flex items-center gap-2.5">
              <div class="w-8 h-8 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center font-bold">
                <i class="bi bi-pencil-square"></i>
              </div>
              <div>
                <h3 class="font-bold text-slate-800 text-sm">Edit Data Anggota</h3>
                <p class="text-[11px] text-slate-500">No. Anggota: <strong class="font-mono text-slate-700">{{ selectedMemberForEdit?.no_anggota }}</strong></p>
              </div>
            </div>
            <button @click="isModalEditAnggotaOpen = false" class="text-slate-400 hover:text-slate-600">
              <i class="bi bi-x-lg"></i>
            </button>
          </div>

          <form @submit.prevent="submitEditAnggota" class="space-y-3">
            <div>
              <label class="font-bold text-slate-700 block mb-1">Nama Lengkap *</label>
              <input v-model="formEditAnggota.nama_lengkap" type="text" required class="w-full px-3.5 py-2 rounded-xl border border-slate-200 font-medium" />
            </div>

            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="font-bold text-slate-700 block mb-1">Tipe Anggota *</label>
                <select v-model="formEditAnggota.tipe_anggota" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 font-medium">
                  <option value="Siswa">Siswa</option>
                  <option value="Guru">Guru</option>
                  <option value="Tendik">Tenaga Kependidikan</option>
                  <option value="Umum">Umum</option>
                  <option value="Alumni">Alumni</option>
                  <option value="Tamu">Tamu</option>
                  <option value="Mitra">Mitra</option>
                </select>
              </div>
              <div>
                <label class="font-bold text-slate-700 block mb-1">Nomor Identitas (NISN/NIP/NIK)</label>
                <input v-model="formEditAnggota.identitas_no" type="text" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 font-medium" />
              </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="font-bold text-slate-700 block mb-1">Kelas / Jurusan / Unit</label>
                <input v-model="formEditAnggota.kelas_jurusan" type="text" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 font-medium" />
              </div>
              <div>
                <label class="font-bold text-slate-700 block mb-1">Jenis Kelamin *</label>
                <select v-model="formEditAnggota.jenis_kelamin" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 font-medium">
                  <option value="L">Laki-laki (L)</option>
                  <option value="P">Perempuan (P)</option>
                </select>
              </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="font-bold text-slate-700 block mb-1">No. WhatsApp / Telepon</label>
                <input v-model="formEditAnggota.no_telepon" type="text" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 font-medium" />
              </div>
              <div>
                <label class="font-bold text-slate-700 block mb-1">Status Keanggotaan</label>
                <select v-model="formEditAnggota.is_active" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 font-medium">
                  <option :value="true">Aktif</option>
                  <option :value="false">Non-Aktif / Diblokir</option>
                </select>
              </div>
            </div>

            <div>
              <label class="font-bold text-slate-700 block mb-1">Alamat Domisili</label>
              <textarea v-model="formEditAnggota.alamat" rows="2" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 font-medium"></textarea>
            </div>

            <div class="pt-3 flex items-center justify-end gap-2 border-t border-slate-100">
              <button type="button" @click="isModalEditAnggotaOpen = false" class="px-4 py-2 rounded-xl bg-slate-100 text-slate-700 font-bold">Batal</button>
              <button type="submit" :disabled="formEditAnggota.processing" class="px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold transition">Simpan Perubahan</button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>
  </AppLayout>
</template>
