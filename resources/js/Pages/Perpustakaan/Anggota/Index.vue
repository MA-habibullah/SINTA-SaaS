<script setup>
import { ref, computed } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link, useForm, router } from '@inertiajs/vue3'

const props = defineProps({
    members: Array,
    anggota_list: Array,
    bukuTamuList: Object,
    pengunjung_list: Array,
    statsTamu: Object,
    stats: Object,
    pengaturan: Object,
    tenants: Array,
    isSuperAdmin: Boolean,
    activeTenantId: String,
    filters: Object,
    siswa_list: Array,
    guru_list: Array,
})

const activeTab = ref('anggota')
const searchQuery = ref(props.filters?.search || '')
const filterTipe = ref(props.filters?.kategori || 'all')
const selectedTenantId = ref(props.filters?.tenant_id || '')

const getSelectedTenantName = () => {
    if (!selectedTenantId.value) return 'Semua Sekolah (Agregat Global)'
    const found = props.tenants?.find(t => t.id === selectedTenantId.value)
    return found ? `${found.nama_sekolah} (${found.npsn})` : 'Sekolah Terpilih'
}

const applyTenantFilter = () => {
    router.get('/perpustakaan/anggota', {
        search: searchQuery.value || undefined,
        kategori: filterTipe.value !== 'all' ? filterTipe.value : undefined,
        tenant_id: selectedTenantId.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    })
}

// Normalized Data Lists
const memberList = computed(() => {
    return props.members || props.anggota_list || []
})

const bukuTamuItems = computed(() => {
    return props.bukuTamuList?.data || props.pengunjung_list || []
})

// Form Tambah Anggota Umum / Manual
const isModalAnggotaOpen = ref(false)
const formAnggota = useForm({
    nomor_anggota: '',
    tipe_anggota: 'umum',
    user_id: '',
    siswa_id: '',
    nama_lengkap: '',
    nomor_identitas: '',
    email: '',
    telepon: '',
    alamat: '',
    masa_berlaku: '',
    is_active: true,
})

const submitAnggota = () => {
    formAnggota.post('/perpustakaan/anggota/store', {
        onSuccess: () => {
            isModalAnggotaOpen.value = false
            formAnggota.reset()
        }
    })
}

// Form Buku Tamu / Presensi Cepat
const isModalBukuTamuOpen = ref(false)
const formBukuTamu = useForm({
    nama_pengunjung: '',
    nomor_identitas: '',
    tipe_pengunjung: 'siswa',
    kelas_atau_instansi: '',
    tujuan_kunjungan: 'Membaca / Meminjam Buku',
    keperluan: '',
})

const submitBukuTamu = () => {
    formBukuTamu.post('/perpustakaan/anggota/buku-tamu', {
        onSuccess: () => {
            isModalBukuTamuOpen.value = false
            formBukuTamu.reset()
        }
    })
}

// Cek Bebas Pustaka Modal
const isModalBebasPustakaOpen = ref(false)
const selectedAnggota = ref(null)
const bebasPustakaResult = ref(null)
const isChecking = ref(false)

const openCekBebasPustaka = async (anggota) => {
    selectedAnggota.value = anggota
    isChecking.value = true
    isModalBebasPustakaOpen.value = true
    try {
        const tipe = anggota.tipe || anggota.tipe_anggota || 'siswa'
        const response = await fetch(`/perpustakaan/anggota/bebas-pustaka?tipe=${tipe}&id=${anggota.id}&tenant_id=${selectedTenantId.value || ''}`)
        const data = await response.json()
        if (data.success) {
            bebasPustakaResult.value = data.data || data
        }
    } catch (e) {
        console.error(e)
    } finally {
        isChecking.value = false
    }
}

// Cetak Surat Bebas Pustaka
const cetakSurat = () => {
    window.print()
}

// Cetak Kartu Anggota Modal
const isModalKartuOpen = ref(false)
const selectedKartu = ref(null)

const openModalKartu = (anggota) => {
    selectedKartu.value = anggota
    isModalKartuOpen.value = true
}

// Form Pengaturan Perpustakaan
const formPengaturan = useForm({
    nama_perpustakaan: props.pengaturan?.nama_perpustakaan || 'Perpustakaan Digital SINTA',
    kepala_perpustakaan: props.pengaturan?.kepala_perpustakaan || '',
    nip_kepala: props.pengaturan?.nip_kepala || '',
    maksimal_pinjam_hari: props.pengaturan?.maksimal_pinjam_hari || props.pengaturan?.max_hari_pinjam_siswa || 7,
    maksimal_buku_siswa: props.pengaturan?.maksimal_buku_siswa || props.pengaturan?.max_buku_pinjam_siswa || 3,
    maksimal_buku_guru: props.pengaturan?.maksimal_buku_guru || props.pengaturan?.max_buku_pinjam_guru || 5,
    tarif_denda_per_hari: props.pengaturan?.tarif_denda_per_hari || 1000,
    is_opac_public: props.pengaturan?.is_opac_public ?? props.pengaturan?.opac_aktif ?? true,
    alamat_perpustakaan: props.pengaturan?.alamat_perpustakaan || '',
})

const submitPengaturan = () => {
    formPengaturan.post('/perpustakaan/anggota/pengaturan', {
        preserveScroll: true,
    })
}

const formatDate = (dateStr) => {
    if (!dateStr) return '-'
    const d = new Date(dateStr)
    return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })
}

const formatDateTime = (dateStr) => {
    if (!dateStr) return '-'
    const d = new Date(dateStr)
    return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}

const formatCurrency = (val) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val || 0)
}

// Computed Filtered Anggota
const filteredAnggota = computed(() => {
    const list = memberList.value
    return list.filter(item => {
        const itemType = (item.tipe || item.tipe_anggota || '').toLowerCase()
        const matchesType = filterTipe.value === 'all' || itemType === filterTipe.value.toLowerCase()
        const name = item.nama || item.nama_lengkap || ''
        const identitas = item.nomor_identitas || item.identitas_no || ''
        const noAnggota = item.nomor_anggota || item.no_anggota || ''
        const matchesQuery = !searchQuery.value || 
            name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            identitas.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            noAnggota.toLowerCase().includes(searchQuery.value.toLowerCase())
        return matchesType && matchesQuery
    })
})
</script>

<template>
    <AppLayout title="Keanggotaan & Administrasi Perpustakaan">
        <div class="space-y-6">
            <!-- Header Halaman -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-50 text-blue-600 border border-blue-200/60">
                            Membership & Visitor Management
                        </span>
                        <span class="text-xs text-slate-400">•</span>
                        <span class="text-xs text-slate-500 font-medium">Bebas Pustaka Digital</span>
                    </div>
                    <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Keanggotaan & Administrasi Perpustakaan</h1>
                    <p class="text-sm text-slate-500 mt-1">Kelola data pemustaka terpadu (Siswa, Guru, Umum), presensi buku tamu, validasi surat bebas pustaka, dan pengaturan sistem.</p>
                </div>
                <div class="flex items-center gap-2">
                    <button @click="isModalBukuTamuOpen = true" 
                            class="px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-sm shadow-sm hover:shadow transition flex items-center gap-2">
                        <i class="bi bi-person-check text-base"></i> Presensi Pengunjung
                    </button>
                    <button @click="isModalAnggotaOpen = true" 
                            class="px-4 py-2.5 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-medium text-sm shadow-sm hover:shadow transition flex items-center gap-2">
                        <i class="bi bi-person-plus text-base"></i> Tambah Anggota Luar
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
                                Menampilkan data keanggotaan perpustakaan milik: <strong class="text-blue-700 font-bold ml-1">{{ getSelectedTenantName() }}</strong>
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

            <!-- Stats Bar -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-2xs flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl shrink-0">
                        <i class="bi bi-people"></i>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-slate-800">{{ stats?.total_anggota || 0 }}</div>
                        <div class="text-xs text-slate-500 font-medium">Total Pemustaka</div>
                    </div>
                </div>
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-2xs flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-xl shrink-0">
                        <i class="bi bi-person-workspace"></i>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-slate-800">{{ stats?.pengunjung_hari_ini || 0 }}</div>
                        <div class="text-xs text-slate-500 font-medium">Pengunjung Hari Ini</div>
                    </div>
                </div>
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-2xs flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-xl shrink-0">
                        <i class="bi bi-calendar3"></i>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-slate-800">{{ stats?.pengunjung_bulan_ini || 0 }}</div>
                        <div class="text-xs text-slate-500 font-medium">Kunjungan Bulan Ini</div>
                    </div>
                </div>
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-2xs flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl shrink-0">
                        <i class="bi bi-shield-check"></i>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-emerald-600">100%</div>
                        <div class="text-xs text-slate-500 font-medium">Integrasi Data Sekolah</div>
                    </div>
                </div>
            </div>

            <!-- Standard Horizontal NavTabs Scroller -->
            <div class="bg-white rounded-2xl shadow-2xs border border-slate-200/80 p-2 relative">
                <div class="flex items-center relative">
                    <button type="button" 
                            class="btn btn-sm btn-light border border-slate-200/80 rounded-xl shadow-2xs me-1.5 hidden md:flex items-center justify-center shrink-0 text-slate-600 hover:text-blue-600 hover:bg-blue-50 transition w-[34px] h-[34px] z-5" 
                            onclick="document.getElementById('navTabsAnggota')?.scrollBy({ left: -220, behavior: 'smooth' })"
                            title="Geser ke Kiri">
                        <i class="bi bi-chevron-left"></i>
                    </button>

                    <div class="nav-tabs-wrapper grow overflow-hidden relative">
                        <ul class="flex border-0 flex-nowrap overflow-x-auto whitespace-nowrap scrollable-nav-tabs gap-1.5 px-1 select-none no-scrollbar" id="navTabsAnggota" role="tablist">
                            <li class="nav-item">
                                <button class="border-0 font-semibold px-4 py-2.5 rounded-xl text-xs transition flex items-center gap-2" 
                                        :class="activeTab === 'anggota' ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'" 
                                        @click="activeTab = 'anggota'">
                                    <i class="bi bi-person-lines-fill text-sm"></i> Direktori Pemustaka
                                    <span class="px-1.5 py-0.2 rounded-full text-[10px]" :class="activeTab === 'anggota' ? 'bg-blue-500 text-white' : 'bg-slate-200 text-slate-700'">
                                        {{ props.anggota_list?.length || 0 }}
                                    </span>
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="border-0 font-semibold px-4 py-2.5 rounded-xl text-xs transition flex items-center gap-2" 
                                        :class="activeTab === 'bukutamu' ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'" 
                                        @click="activeTab = 'bukutamu'">
                                    <i class="bi bi-journal-text text-sm"></i> Buku Tamu & Presensi Kunjungan
                                    <span class="px-1.5 py-0.2 rounded-full text-[10px]" :class="activeTab === 'bukutamu' ? 'bg-blue-500 text-white' : 'bg-slate-200 text-slate-700'">
                                        {{ props.pengunjung_list?.length || 0 }}
                                    </span>
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="border-0 font-semibold px-4 py-2.5 rounded-xl text-xs transition flex items-center gap-2" 
                                        :class="activeTab === 'pengaturan' ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'" 
                                        @click="activeTab = 'pengaturan'">
                                    <i class="bi bi-sliders text-sm"></i> Pengaturan & Kebijakan Sirkulasi
                                </button>
                            </li>
                        </ul>
                    </div>

                    <button type="button" 
                            class="btn btn-sm btn-light border border-slate-200/80 rounded-xl shadow-2xs ms-1.5 hidden md:flex items-center justify-center shrink-0 text-slate-600 hover:text-blue-600 hover:bg-blue-50 transition w-[34px] h-[34px] z-5" 
                            onclick="document.getElementById('navTabsAnggota')?.scrollBy({ left: 220, behavior: 'smooth' })"
                            title="Geser ke Kanan">
                        <i class="bi bi-chevron-right"></i>
                    </button>
                </div>
            </div>

            <!-- Tab 1: Direktori Pemustaka -->
            <div v-if="activeTab === 'anggota'" class="space-y-4">
                <div class="flex flex-col md:flex-row items-center justify-between gap-3 bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs">
                    <div class="flex flex-wrap items-center gap-2.5 w-full md:w-auto">
                        <div class="relative w-full md:w-72">
                            <i class="bi bi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                            <input v-model="searchQuery" type="text" placeholder="Cari nama, NIS, nomor anggota..." 
                                   class="w-full pl-9 pr-4 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500 bg-slate-50/50">
                        </div>
                        <select v-model="filterTipe" class="text-xs rounded-xl border border-slate-200 py-2 px-3 bg-slate-50/50">
                            <option value="all">Semua Tipe Anggota</option>
                            <option value="siswa">Siswa Terdaftar</option>
                            <option value="guru">Guru / Pendidik</option>
                            <option value="staff">Tenaga Kependidikan</option>
                            <option value="umum">Anggota Umum / Luar</option>
                        </select>
                    </div>
                    <div class="text-xs text-slate-500">
                        Menampilkan <strong class="text-slate-800">{{ filteredAnggota.length }}</strong> pemustaka
                    </div>
                </div>

                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs text-slate-600">
                            <thead class="bg-slate-50/80 text-slate-700 font-semibold border-b border-slate-200/80 uppercase tracking-wider text-[11px]">
                                <tr>
                                    <th class="px-5 py-3.5">No. Anggota / Pemustaka</th>
                                    <th class="px-5 py-3.5">Kategori / Identitas</th>
                                    <th class="px-5 py-3.5">Pinjaman Aktif & Denda</th>
                                    <th class="px-5 py-3.5">Status Bebas Pustaka</th>
                                    <th class="px-5 py-3.5 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="item in filteredAnggota" :key="item.id" class="hover:bg-slate-50/60 transition">
                                    <td class="px-5 py-4">
                                        <div class="font-bold text-slate-800 text-sm">{{ item.nama }}</div>
                                        <div class="text-slate-400 font-mono text-[11px] mt-0.5">
                                            No: <span class="text-blue-600 font-semibold">{{ item.nomor_anggota }}</span>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4">
                                        <div class="flex items-center gap-1.5">
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase" 
                                                  :class="item.tipe === 'siswa' ? 'bg-blue-100 text-blue-700' : (item.tipe === 'guru' ? 'bg-emerald-100 text-emerald-700' : 'bg-purple-100 text-purple-700')">
                                                {{ item.tipe }}
                                            </span>
                                            <span class="font-mono text-slate-700">{{ item.nomor_identitas || '-' }}</span>
                                        </div>
                                        <div v-if="item.sub_info" class="text-slate-400 text-[11px] mt-0.5">{{ item.sub_info }}</div>
                                    </td>
                                    <td class="px-5 py-4">
                                        <div class="flex items-center gap-2">
                                            <span class="px-2 py-0.5 rounded-full text-xs font-semibold" :class="item.pinjaman_aktif > 0 ? 'bg-amber-50 text-amber-600 border border-amber-200' : 'bg-slate-100 text-slate-600'">
                                                {{ item.pinjaman_aktif || 0 }} Buku Dipinjam
                                            </span>
                                            <span v-if="item.total_denda > 0" class="text-rose-600 font-bold text-xs">
                                                Denda: {{ formatCurrency(item.total_denda) }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4">
                                        <span v-if="item.is_bebas_pustaka" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-600 border border-emerald-200">
                                            <i class="bi bi-shield-check"></i> Bebas Tanggungan
                                        </span>
                                        <span v-else class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-50 text-rose-600 border border-rose-200">
                                            <i class="bi bi-exclamation-octagon"></i> Ada Tanggungan
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 text-right">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <button @click="openCekBebasPustaka(item)" 
                                                    class="px-3 py-1.5 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-600 border border-emerald-200 font-medium text-xs transition flex items-center gap-1"
                                                    title="Validasi & Cetak Surat Bebas Pustaka">
                                                <i class="bi bi-file-earmark-check"></i> Bebas Pustaka
                                            </button>
                                            <button @click="openModalKartu(item)" 
                                                    class="px-3 py-1.5 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-600 border border-blue-200 font-medium text-xs transition flex items-center gap-1"
                                                    title="Cetak Kartu Pemustaka">
                                                <i class="bi bi-person-badge"></i> Kartu
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="!filteredAnggota.length">
                                    <td colspan="5" class="px-5 py-10 text-center text-slate-400">
                                        Tidak ada anggota ditemukan.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Tab 2: Buku Tamu & Presensi Kunjungan -->
            <div v-if="activeTab === 'bukutamu'" class="space-y-4">
                <div class="flex items-center justify-between bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs">
                    <div>
                        <h3 class="text-base font-bold text-slate-800">Log Presensi Kunjungan Perpustakaan</h3>
                        <p class="text-xs text-slate-500">Catatan pengunjung membaca, meminjam, ataupun riset digital.</p>
                    </div>
                    <button @click="isModalBukuTamuOpen = true" class="px-3.5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-xs transition flex items-center gap-1.5">
                        <i class="bi bi-plus-lg"></i> Input Presensi Kunjungan
                    </button>
                </div>

                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs text-slate-600">
                            <thead class="bg-slate-50/80 text-slate-700 font-semibold border-b border-slate-200/80 uppercase tracking-wider text-[11px]">
                                <tr>
                                    <th class="px-5 py-3.5">Waktu Kunjungan</th>
                                    <th class="px-5 py-3.5">Nama Pemustaka</th>
                                    <th class="px-5 py-3.5">Identitas & Kategori</th>
                                    <th class="px-5 py-3.5">Tujuan / Keperluan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="tamu in props.pengunjung_list" :key="tamu.id" class="hover:bg-slate-50/60">
                                    <td class="px-5 py-3.5 text-slate-700 font-medium whitespace-nowrap">
                                        <i class="bi bi-clock me-1 text-slate-400"></i> {{ formatDateTime(tamu.waktu_kunjungan || tamu.created_at) }}
                                    </td>
                                    <td class="px-5 py-3.5 font-bold text-slate-800">{{ tamu.nama_pengunjung }}</td>
                                    <td class="px-5 py-3.5">
                                        <div class="flex items-center gap-1.5">
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-slate-100 text-slate-600">{{ tamu.tipe_pengunjung }}</span>
                                            <span>{{ tamu.nomor_identitas || tamu.kelas_atau_instansi || '-' }}</span>
                                        </div>
                                    </td>
                                    <td class="px-5 py-3.5">
                                        <span class="font-medium text-slate-800">{{ tamu.tujuan_kunjungan || 'Membaca' }}</span>
                                        <span v-if="tamu.keperluan" class="text-slate-400 block text-[11px]">{{ tamu.keperluan }}</span>
                                    </td>
                                </tr>
                                <tr v-if="!props.pengunjung_list?.length">
                                    <td colspan="4" class="px-5 py-8 text-center text-slate-400">Belum ada data kunjungan hari ini.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Tab 3: Pengaturan Kebijakan Perpustakaan -->
            <div v-if="activeTab === 'pengaturan'" class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs max-w-3xl">
                <div class="flex items-center gap-3 mb-6 pb-4 border-b border-slate-100">
                    <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl">
                        <i class="bi bi-sliders"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-800">Pengaturan & Kebijakan Sirkulasi Perpustakaan</h3>
                        <p class="text-xs text-slate-500">Konfigurasi aturan peminjaman, denda keterlambatan, dan identitas perpustakaan.</p>
                    </div>
                </div>

                <form @submit.prevent="submitPengaturan" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Perpustakaan</label>
                            <input v-model="formPengaturan.nama_perpustakaan" type="text" class="w-full text-xs rounded-xl border border-slate-200 py-2.5 px-3">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Kepala Perpustakaan</label>
                            <input v-model="formPengaturan.kepala_perpustakaan" type="text" class="w-full text-xs rounded-xl border border-slate-200 py-2.5 px-3">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">NIP Kepala Perpustakaan</label>
                            <input v-model="formPengaturan.nip_kepala" type="text" class="w-full text-xs rounded-xl border border-slate-200 py-2.5 px-3">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Tarif Denda per Hari (Rp)</label>
                            <input v-model.number="formPengaturan.tarif_denda_per_hari" type="number" min="0" class="w-full text-xs rounded-xl border border-slate-200 py-2.5 px-3 font-bold text-rose-600">
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Maksimal Hari Pinjam</label>
                            <input v-model.number="formPengaturan.maksimal_pinjam_hari" type="number" min="1" class="w-full text-xs rounded-xl border border-slate-200 py-2.5 px-3">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Maks Buku (Siswa)</label>
                            <input v-model.number="formPengaturan.maksimal_buku_siswa" type="number" min="1" class="w-full text-xs rounded-xl border border-slate-200 py-2.5 px-3">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Maks Buku (Guru)</label>
                            <input v-model.number="formPengaturan.maksimal_buku_guru" type="number" min="1" class="w-full text-xs rounded-xl border border-slate-200 py-2.5 px-3">
                        </div>
                    </div>

                    <div class="pt-2">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input v-model="formPengaturan.is_opac_public" type="checkbox" class="rounded text-blue-600 focus:ring-blue-500">
                            <span class="text-xs font-semibold text-slate-700">Aktifkan Akses OPAC Publik (Dapat Diakses Tanpa Login)</span>
                        </label>
                    </div>

                    <div class="pt-4 border-t border-slate-100 flex items-center justify-end">
                        <button type="submit" :disabled="formPengaturan.processing" class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-medium text-xs shadow transition flex items-center gap-1.5">
                            <i class="bi bi-save"></i> Simpan Konfigurasi Kebijakan
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- TELEPORT MODAL BEBAS PUSTAKA -->
        <Teleport to="body">
            <div v-if="isModalBebasPustakaOpen" class="fixed inset-0 z-[9999] flex items-center justify-center overflow-y-auto p-4 sm:p-6">
                <div class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm transition-opacity" @click="isModalBebasPustakaOpen = false"></div>
                <div class="relative w-full max-w-xl bg-white rounded-2xl shadow-2xl border border-slate-100 overflow-hidden transform transition-all">
                    <div class="p-6 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                        <div class="flex items-center gap-2.5">
                            <div class="w-9 h-9 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center text-lg font-bold">
                                <i class="bi bi-shield-check"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-slate-800 text-base">Surat Keterangan Bebas Pustaka</h3>
                                <p class="text-xs text-slate-500">Verifikasi bebas tanggungan pinjaman dan denda.</p>
                            </div>
                        </div>
                        <button @click="isModalBebasPustakaOpen = false" class="text-slate-400 hover:text-slate-600 transition">
                            <i class="bi bi-x-lg text-lg"></i>
                        </button>
                    </div>

                    <div class="p-6 space-y-4">
                        <div v-if="isChecking" class="py-8 text-center text-slate-400">
                            <div class="animate-spin text-2xl mb-2 text-blue-600"><i class="bi bi-arrow-repeat"></i></div>
                            Memeriksa status sirkulasi dan denda...
                        </div>
                        <div v-else-if="bebasPustakaResult" class="space-y-4">
                            <!-- Status Banner -->
                            <div class="p-4 rounded-xl flex items-start gap-3" 
                                 :class="bebasPustakaResult.is_bebas_pustaka ? 'bg-emerald-50 border border-emerald-200 text-emerald-800' : 'bg-rose-50 border border-rose-200 text-rose-800'">
                                <i class="text-xl" :class="bebasPustakaResult.is_bebas_pustaka ? 'bi bi-check-circle-fill text-emerald-600' : 'bi bi-x-circle-fill text-rose-600'"></i>
                                <div>
                                    <h4 class="font-bold text-sm">{{ bebasPustakaResult.is_bebas_pustaka ? 'MEMENUHI SYARAT BEBAS PUSTAKA' : 'BELUM BEBAS PUSTAKA' }}</h4>
                                    <p class="text-xs mt-0.5">{{ bebasPustakaResult.catatan }}</p>
                                </div>
                            </div>

                            <!-- Preview Surat -->
                            <div class="border border-slate-200 rounded-xl p-6 bg-slate-50/40 text-xs space-y-3 font-sans" id="suratBebasPustaka">
                                <div class="text-center border-b border-slate-300 pb-3">
                                    <div class="font-bold text-slate-800 uppercase text-sm">SURAT KETERANGAN BEBAS PERPUSTAKAAN</div>
                                    <div class="text-[11px] text-slate-500">Nomor: {{ bebasPustakaResult.nomor_surat }}</div>
                                </div>
                                <div class="space-y-1.5 text-slate-700">
                                    <p>Yang bertanda tangan di bawah ini Kepala Perpustakaan menerangkan bahwa:</p>
                                    <div class="grid grid-cols-3 gap-1 pt-1 font-medium">
                                        <span class="text-slate-500">Nama Lengkap</span>
                                        <span class="col-span-2 text-slate-800">: {{ bebasPustakaResult.anggota?.nama }}</span>
                                        <span class="text-slate-500">No. Identitas / NIS</span>
                                        <span class="col-span-2 text-slate-800">: {{ bebasPustakaResult.anggota?.nomor_identitas || '-' }}</span>
                                        <span class="text-slate-500">Status / Kategori</span>
                                        <span class="col-span-2 text-slate-800 uppercase">: {{ bebasPustakaResult.anggota?.tipe }}</span>
                                    </div>
                                    <p class="pt-2">Dinyatakan <strong>BEBAS DARI SEGALA PINJAMAN DAN TANGGUNGAN DENDA</strong> di Perpustakaan Sekolah per tanggal cetak surat ini.</p>
                                </div>
                                <div class="pt-4 flex justify-between items-end text-[11px]">
                                    <div>
                                        <div class="text-slate-400">Verifikasi Digital: SINTA Cloud</div>
                                    </div>
                                    <div class="text-center">
                                        <div>Kepala Perpustakaan,</div>
                                        <div class="h-10"></div>
                                        <div class="font-bold text-slate-800 underline">{{ props.pengaturan?.kepala_perpustakaan || 'Kepala Perpustakaan' }}</div>
                                        <div class="text-slate-500">NIP. {{ props.pengaturan?.nip_kepala || '-' }}</div>
                                    </div>
                                </div>
                            </div>

                            <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-2">
                                <button type="button" @click="isModalBebasPustakaOpen = false" class="px-4 py-2.5 rounded-xl border border-slate-200 text-slate-600 font-medium text-xs hover:bg-slate-50 transition">
                                    Tutup
                                </button>
                                <button v-if="bebasPustakaResult.is_bebas_pustaka" @click="cetakSurat" class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-xs shadow transition flex items-center gap-1.5">
                                    <i class="bi bi-printer"></i> Cetak Surat Resmi
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- TELEPORT MODAL CETAK KARTU ANGGOTA -->
        <Teleport to="body">
            <div v-if="isModalKartuOpen" class="fixed inset-0 z-[9999] flex items-center justify-center overflow-y-auto p-4 sm:p-6">
                <div class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm transition-opacity" @click="isModalKartuOpen = false"></div>
                <div class="relative w-full max-w-md bg-white rounded-2xl shadow-2xl border border-slate-100 overflow-hidden transform transition-all">
                    <div class="p-6 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                        <div class="flex items-center gap-2.5">
                            <div class="w-9 h-9 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center text-lg font-bold">
                                <i class="bi bi-person-badge"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-slate-800 text-base">Kartu Anggota Perpustakaan</h3>
                                <p class="text-xs text-slate-500">Preview kartu pemustaka digital.</p>
                            </div>
                        </div>
                        <button @click="isModalKartuOpen = false" class="text-slate-400 hover:text-slate-600 transition">
                            <i class="bi bi-x-lg text-lg"></i>
                        </button>
                    </div>

                    <div class="p-6 space-y-4">
                        <div v-if="selectedKartu" class="bg-gradient-to-br from-slate-900 via-blue-900 to-indigo-950 p-6 rounded-2xl text-white shadow-lg relative overflow-hidden">
                            <div class="flex items-center justify-between border-b border-white/20 pb-3 mb-4">
                                <div>
                                    <div class="text-[10px] font-bold tracking-widest uppercase text-blue-300">KARTU ANGGOTA PERPUSTAKAAN</div>
                                    <div class="text-xs font-semibold">{{ props.pengaturan?.nama_perpustakaan || 'SINTA Library' }}</div>
                                </div>
                                <i class="bi bi-book-half text-2xl text-blue-400"></i>
                            </div>

                            <div class="flex items-center gap-4">
                                <div class="w-16 h-20 rounded-xl bg-white/10 border border-white/20 flex items-center justify-center text-2xl text-white/50 shrink-0">
                                    <i class="bi bi-person-fill"></i>
                                </div>
                                <div class="space-y-1">
                                    <div class="font-bold text-sm text-white">{{ selectedKartu.nama }}</div>
                                    <div class="text-xs text-blue-200 font-mono">{{ selectedKartu.nomor_identitas || '-' }}</div>
                                    <div class="inline-block px-2 py-0.5 rounded bg-blue-500/30 text-blue-200 text-[10px] font-semibold uppercase">
                                        {{ selectedKartu.tipe }}
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4 pt-3 border-t border-white/10 flex items-center justify-between text-[10px] text-slate-300">
                                <span class="font-mono">NO: {{ selectedKartu.nomor_anggota }}</span>
                                <span>Berlaku Selama Aktif</span>
                            </div>
                        </div>

                        <div class="pt-2 flex items-center justify-end gap-2">
                            <button type="button" @click="isModalKartuOpen = false" class="px-4 py-2.5 rounded-xl border border-slate-200 text-slate-600 font-medium text-xs hover:bg-slate-50 transition">
                                Tutup
                            </button>
                            <button @click="cetakSurat" class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-medium text-xs shadow transition flex items-center gap-1.5">
                                <i class="bi bi-printer"></i> Cetak Kartu
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- TELEPORT MODAL TAMBAH ANGGOTA UMUM -->
        <Teleport to="body">
            <div v-if="isModalAnggotaOpen" class="fixed inset-0 z-[9999] flex items-center justify-center overflow-y-auto p-4 sm:p-6">
                <div class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm transition-opacity" @click="isModalAnggotaOpen = false"></div>
                <div class="relative w-full max-w-lg bg-white rounded-2xl shadow-2xl border border-slate-100 overflow-hidden transform transition-all">
                    <div class="p-6 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                        <div class="flex items-center gap-2.5">
                            <div class="w-9 h-9 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center text-lg font-bold">
                                <i class="bi bi-person-plus"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-slate-800 text-base">Registrasi Anggota Umum / Luar</h3>
                                <p class="text-xs text-slate-500">Pendaftaran pemustaka eksternal sekolah.</p>
                            </div>
                        </div>
                        <button @click="isModalAnggotaOpen = false" class="text-slate-400 hover:text-slate-600 transition">
                            <i class="bi bi-x-lg text-lg"></i>
                        </button>
                    </div>

                    <form @submit.prevent="submitAnggota" class="p-6 space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Lengkap *</label>
                            <input v-model="formAnggota.nama_lengkap" type="text" required class="w-full text-xs rounded-xl border border-slate-200 py-2.5 px-3">
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">NIK / No. KTP *</label>
                                <input v-model="formAnggota.nomor_identitas" type="text" required class="w-full text-xs rounded-xl border border-slate-200 py-2.5 px-3">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">No. HP / WhatsApp</label>
                                <input v-model="formAnggota.telepon" type="text" class="w-full text-xs rounded-xl border border-slate-200 py-2.5 px-3">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Email</label>
                            <input v-model="formAnggota.email" type="email" class="w-full text-xs rounded-xl border border-slate-200 py-2.5 px-3">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Alamat Tinggal</label>
                            <textarea v-model="formAnggota.alamat" rows="2" class="w-full text-xs rounded-xl border border-slate-200 py-2.5 px-3"></textarea>
                        </div>

                        <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-2">
                            <button type="button" @click="isModalAnggotaOpen = false" class="px-4 py-2.5 rounded-xl border border-slate-200 text-slate-600 font-medium text-xs hover:bg-slate-50 transition">
                                Batal
                            </button>
                            <button type="submit" :disabled="formAnggota.processing" class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-medium text-xs shadow transition flex items-center gap-1.5">
                                <i class="bi bi-check2"></i> Daftarkan Anggota
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- TELEPORT MODAL PRESENSI BUKU TAMU -->
        <Teleport to="body">
            <div v-if="isModalBukuTamuOpen" class="fixed inset-0 z-[9999] flex items-center justify-center overflow-y-auto p-4 sm:p-6">
                <div class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm transition-opacity" @click="isModalBukuTamuOpen = false"></div>
                <div class="relative w-full max-w-md bg-white rounded-2xl shadow-2xl border border-slate-100 overflow-hidden transform transition-all">
                    <div class="p-6 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                        <div class="flex items-center gap-2.5">
                            <div class="w-9 h-9 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center text-lg font-bold">
                                <i class="bi bi-person-check"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-slate-800 text-base">Presensi Pengunjung / Tamu</h3>
                                <p class="text-xs text-slate-500">Catat kehadiran pemustaka di perpustakaan.</p>
                            </div>
                        </div>
                        <button @click="isModalBukuTamuOpen = false" class="text-slate-400 hover:text-slate-600 transition">
                            <i class="bi bi-x-lg text-lg"></i>
                        </button>
                    </div>

                    <form @submit.prevent="submitBukuTamu" class="p-6 space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Pemustaka *</label>
                            <input v-model="formBukuTamu.nama_pengunjung" type="text" required class="w-full text-xs rounded-xl border border-slate-200 py-2.5 px-3" placeholder="Nama lengkap...">
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Kategori</label>
                                <select v-model="formBukuTamu.tipe_pengunjung" class="w-full text-xs rounded-xl border border-slate-200 py-2.5 px-3">
                                    <option value="siswa">Siswa</option>
                                    <option value="guru">Guru</option>
                                    <option value="staff">Staff</option>
                                    <option value="umum">Umum</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">NIS / NIP / Instansi</label>
                                <input v-model="formBukuTamu.nomor_identitas" type="text" class="w-full text-xs rounded-xl border border-slate-200 py-2.5 px-3" placeholder="NIS/NIP...">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Tujuan Kunjungan *</label>
                            <select v-model="formBukuTamu.tujuan_kunjungan" class="w-full text-xs rounded-xl border border-slate-200 py-2.5 px-3">
                                <option value="Membaca Buku">Membaca Buku di Tempat</option>
                                <option value="Meminjam / Mengembalikan Buku">Meminjam / Mengembalikan Buku</option>
                                <option value="Riset / Tugas Sekolah">Riset / Tugas Sekolah</option>
                                <option value="Akses E-Book / Komputer">Akses E-Book / Komputer</option>
                                <option value="Lainnya">Lainnya</option>
                            </select>
                        </div>

                        <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-2">
                            <button type="button" @click="isModalBukuTamuOpen = false" class="px-4 py-2.5 rounded-xl border border-slate-200 text-slate-600 font-medium text-xs hover:bg-slate-50 transition">
                                Batal
                            </button>
                            <button type="submit" :disabled="formBukuTamu.processing" class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-xs shadow transition flex items-center gap-1.5">
                                <i class="bi bi-check2"></i> Simpan Presensi
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>
