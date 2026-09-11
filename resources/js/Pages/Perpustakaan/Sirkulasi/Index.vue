<script setup>
import { ref, computed } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link, useForm, router } from '@inertiajs/vue3'

const props = defineProps({
    sirkulasiAktif: Object,
    sirkulasiRiwayat: Object,
    sirkulasi_aktif: Array,
    sirkulasi_selesai: Array,
    paketList: Array,
    paket_list: Array,
    dendaList: Object,
    denda_list: Array,
    bukuTersedia: Array,
    buku_list: Array,
    anggotaSelector: Array,
    member_list: Array,
    kelas_list: Array,
    stats: Object,
    pengaturan: Object,
    tenants: Array,
    isSuperAdmin: Boolean,
    activeTenantId: String,
    filters: Object,
})

const activeTab = ref('aktif')
const searchQuery = ref(props.filters?.search || '')
const selectedTenantId = ref(props.filters?.tenant_id || '')

const getSelectedTenantName = () => {
    if (!selectedTenantId.value) return 'Semua Sekolah (Agregat Global)'
    const found = props.tenants?.find(t => t.id === selectedTenantId.value)
    return found ? `${found.nama_sekolah} (${found.npsn})` : 'Sekolah Terpilih'
}

const applyTenantFilter = () => {
    router.get('/perpustakaan/sirkulasi', {
        search: searchQuery.value || undefined,
        tenant_id: selectedTenantId.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    })
}

// Normalized Data Lists
const sirkulasiAktifList = computed(() => {
    return props.sirkulasiAktif?.data || props.sirkulasi_aktif || []
})

const sirkulasiRiwayatList = computed(() => {
    return props.sirkulasiRiwayat?.data || props.sirkulasi_selesai || []
})

const dendaItems = computed(() => {
    return props.dendaList?.data || props.denda_list || []
})

const pakets = computed(() => {
    return props.paketList || props.paket_list || []
})

const bukuOptions = computed(() => {
    return props.bukuTersedia || props.buku_list || []
})

const memberOptions = computed(() => {
    return props.anggotaSelector || props.member_list || []
})

// Form Peminjaman Kios / Cepat
const isModalPinjamOpen = ref(false)
const formPinjam = useForm({
    tipe_peminjam: 'siswa',
    peminjam_id: '',
    peminjam_nama: '',
    peminjam_identitas: '',
    buku_id: '',
    kode_eksemplar: '',
    lama_hari: 7,
    keterangan: '',
})

// Quick Member Selector
const onSelectMember = (event) => {
    const selectedId = event.target.value
    const found = memberOptions.value.find(m => m.id === selectedId)
    if (found) {
        formPinjam.peminjam_id = found.id
        formPinjam.peminjam_nama = found.nama || found.nama_lengkap
        formPinjam.peminjam_identitas = found.nomor_identitas || found.identitas_no
        formPinjam.tipe_peminjam = (found.tipe || found.tipe_anggota || 'siswa').toLowerCase()
    }
}

// Quick Book Selector
const onSelectBuku = (event) => {
    const selectedId = event.target.value
    const found = bukuOptions.value.find(b => b.id === selectedId)
    if (found) {
        formPinjam.buku_id = found.id
        formPinjam.kode_eksemplar = found.kode_buku ? found.kode_buku + '-01' : ''
    }
}

const submitPinjam = () => {
    formPinjam.post('/perpustakaan/sirkulasi/pinjam', {
        onSuccess: () => {
            isModalPinjamOpen.value = false
            formPinjam.reset()
        }
    })
}

// Return / Kembalikan Book
const isModalKembaliOpen = ref(false)
const selectedSirkulasi = ref(null)
const formKembali = useForm({
    sirkulasi_id: '',
    kondisi_kembali: 'baik',
    denda: 0,
    keterangan_kembali: '',
})

const openModalKembali = (item) => {
    selectedSirkulasi.value = item
    formKembali.sirkulasi_id = item.id
    formKembali.kondisi_kembali = 'baik'
    const hariTerlambat = item.terlambat_hari || item.hari_keterlambatan || 0
    formKembali.denda = hariTerlambat > 0 ? hariTerlambat * 1000 : 0
    formKembali.keterangan_kembali = ''
    isModalKembaliOpen.value = true
}

const submitKembali = () => {
    formKembali.post('/perpustakaan/sirkulasi/kembalikan', {
        onSuccess: () => {
            isModalKembaliOpen.value = false
            selectedSirkulasi.value = null
        }
    })
}

// Extend / Perpanjang Peminjaman
const perpanjangPinjaman = (item) => {
    const judul = item.judul_buku || item.buku?.judul_buku || 'Buku'
    const nama = item.peminjam_nama || item.nama_peminjam
    if (confirm(`Perpanjang peminjaman buku "${judul}" selama 7 hari lagi untuk ${nama}?`)) {
        router.post('/perpustakaan/sirkulasi/perpanjang', {
            sirkulasi_id: item.id,
            tambah_hari: 7
        })
    }
}

// Bayar Denda Form
const isModalBayarDendaOpen = ref(false)
const selectedDenda = ref(null)
const formBayarDenda = useForm({
    sirkulasi_id: '',
    jumlah_bayar: 0,
    metode_bayar: 'tunai',
    catatan_bayar: '',
})

const openModalBayarDenda = (item) => {
    selectedDenda.value = item
    formBayarDenda.sirkulasi_id = item.id
    const dendaTotal = item.total_denda || item.denda_keterlambatan || 0
    const dendaBayar = item.denda_dibayar || 0
    formBayarDenda.jumlah_bayar = Math.max(0, dendaTotal - dendaBayar)
    isModalBayarDendaOpen.value = true
}

const submitBayarDenda = () => {
    formBayarDenda.post('/perpustakaan/sirkulasi/bayar-denda', {
        onSuccess: () => {
            isModalBayarDendaOpen.value = false
            selectedDenda.value = null
        }
    })
}

// Form Distribusi Paket Buku
const isModalPaketOpen = ref(false)
const formPaket = useForm({
    nama_paket: '',
    kode_paket: '',
    kelas_id: '',
    tahun_ajaran: '2026/2027',
    semester: '1',
    mata_pelajaran: '',
    jumlah_eksemplar: 30,
    keterangan: '',
})

const submitPaket = () => {
    formPaket.post('/perpustakaan/sirkulasi/paket-buku', {
        onSuccess: () => {
            isModalPaketOpen.value = false
            formPaket.reset()
        }
    })
}

const formatCurrency = (val) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val || 0)
}

const formatDate = (dateStr) => {
    if (!dateStr) return '-'
    const d = new Date(dateStr)
    return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })
}

// Filtered data computed
const filteredAktif = computed(() => {
    const list = sirkulasiAktifList.value
    if (!searchQuery.value) return list
    const q = searchQuery.value.toLowerCase()
    return list.filter(i => 
        (i.peminjam_nama && i.peminjam_nama.toLowerCase().includes(q)) ||
        (i.nama_peminjam && i.nama_peminjam.toLowerCase().includes(q)) ||
        (i.nomor_identitas && i.nomor_identitas.toLowerCase().includes(q)) ||
        (i.judul_buku && i.judul_buku.toLowerCase().includes(q)) ||
        (i.buku?.judul_buku && i.buku.judul_buku.toLowerCase().includes(q)) ||
        (i.kode_transaksi && i.kode_transaksi.toLowerCase().includes(q)) ||
        (i.nomor_transaksi && i.nomor_transaksi.toLowerCase().includes(q))
    )
})

const filteredSelesai = computed(() => {
    const list = sirkulasiRiwayatList.value
    if (!searchQuery.value) return list
    const q = searchQuery.value.toLowerCase()
    return list.filter(i => 
        (i.peminjam_nama && i.peminjam_nama.toLowerCase().includes(q)) ||
        (i.nama_peminjam && i.nama_peminjam.toLowerCase().includes(q)) ||
        (i.judul_buku && i.judul_buku.toLowerCase().includes(q)) ||
        (i.buku?.judul_buku && i.buku.judul_buku.toLowerCase().includes(q)) ||
        (i.kode_transaksi && i.kode_transaksi.toLowerCase().includes(q)) ||
        (i.nomor_transaksi && i.nomor_transaksi.toLowerCase().includes(q))
    )
})
</script>

<template>
    <AppLayout title="Sirkulasi & Layanan Peminjaman">
        <div class="space-y-6">
            <!-- Header Halaman -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-600 border border-emerald-200/60">
                            Circulation Desk & Self-Service
                        </span>
                        <span class="text-xs text-slate-400">•</span>
                        <span class="text-xs text-slate-500 font-medium">Sirkulasi Real-Time</span>
                    </div>
                    <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Sirkulasi & Layanan Peminjaman</h1>
                    <p class="text-sm text-slate-500 mt-1">Kelola transaksi peminjaman, pengembalian instan, perpanjangan, paket buku kelas, dan rekap denda.</p>
                </div>
                <div class="flex items-center gap-2">
                    <button @click="isModalPinjamOpen = true" 
                            class="px-4 py-2.5 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-medium text-sm shadow-sm hover:shadow transition flex items-center gap-2">
                        <i class="bi bi-box-arrow-up-right text-base"></i> Transaksi Pinjam Baru
                    </button>
                    <button @click="isModalPaketOpen = true" 
                            class="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium text-sm transition flex items-center gap-2 border border-slate-200/80">
                        <i class="bi bi-collection text-base"></i> Distribusi Paket
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
                                Menampilkan data sirkulasi perpustakaan milik: <strong class="text-blue-700 font-bold ml-1">{{ getSelectedTenantName() }}</strong>
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
                        <i class="bi bi-arrow-left-right"></i>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-slate-800">{{ stats?.total_dipinjam || 0 }}</div>
                        <div class="text-xs text-slate-500 font-medium">Sedang Dipinjam</div>
                    </div>
                </div>
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-2xs flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center text-xl shrink-0">
                        <i class="bi bi-clock-history"></i>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-rose-600">{{ stats?.total_terlambat || 0 }}</div>
                        <div class="text-xs text-slate-500 font-medium">Terlambat / Jatuh Tempo</div>
                    </div>
                </div>
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-2xs flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl shrink-0">
                        <i class="bi bi-cash-stack"></i>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-slate-800">{{ formatCurrency(stats?.total_denda_pending) }}</div>
                        <div class="text-xs text-slate-500 font-medium">Denda Belum Lunas</div>
                    </div>
                </div>
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-2xs flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl shrink-0">
                        <i class="bi bi-check2-circle"></i>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-slate-800">{{ stats?.kembali_bulan_ini || 0 }}</div>
                        <div class="text-xs text-slate-500 font-medium">Kembali Bulan Ini</div>
                    </div>
                </div>
            </div>

            <!-- Standard Horizontal NavTabs Scroller -->
            <div class="bg-white rounded-2xl shadow-2xs border border-slate-200/80 p-2 relative">
                <div class="flex items-center relative">
                    <button type="button" 
                            class="btn btn-sm btn-light border border-slate-200/80 rounded-xl shadow-2xs me-1.5 hidden md:flex items-center justify-center shrink-0 text-slate-600 hover:text-blue-600 hover:bg-blue-50 transition w-[34px] h-[34px] z-5" 
                            onclick="document.getElementById('navTabsSirkulasi')?.scrollBy({ left: -220, behavior: 'smooth' })"
                            title="Geser ke Kiri">
                        <i class="bi bi-chevron-left"></i>
                    </button>

                    <div class="nav-tabs-wrapper grow overflow-hidden relative">
                        <ul class="flex border-0 flex-nowrap overflow-x-auto whitespace-nowrap scrollable-nav-tabs gap-1.5 px-1 select-none no-scrollbar" id="navTabsSirkulasi" role="tablist">
                            <li class="nav-item">
                                <button class="border-0 font-semibold px-4 py-2.5 rounded-xl text-xs transition flex items-center gap-2" 
                                        :class="activeTab === 'aktif' ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'" 
                                        @click="activeTab = 'aktif'">
                                    <i class="bi bi-hourglass-split text-sm"></i> Peminjaman Aktif 
                                    <span class="px-1.5 py-0.2 rounded-full text-[10px]" :class="activeTab === 'aktif' ? 'bg-blue-500 text-white' : 'bg-slate-200 text-slate-700'">
                                        {{ props.sirkulasi_aktif?.length || 0 }}
                                    </span>
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="border-0 font-semibold px-4 py-2.5 rounded-xl text-xs transition flex items-center gap-2" 
                                        :class="activeTab === 'selesai' ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'" 
                                        @click="activeTab = 'selesai'">
                                    <i class="bi bi-clock-history text-sm"></i> Riwayat Sirkulasi Selesai
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="border-0 font-semibold px-4 py-2.5 rounded-xl text-xs transition flex items-center gap-2" 
                                        :class="activeTab === 'denda' ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'" 
                                        @click="activeTab = 'denda'">
                                    <i class="bi bi-cash-coin text-sm"></i> Rekap Denda Keterlambatan
                                    <span v-if="props.denda_list?.length" class="px-1.5 py-0.2 rounded-full text-[10px] bg-rose-500 text-white">
                                        {{ props.denda_list?.length }}
                                    </span>
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="border-0 font-semibold px-4 py-2.5 rounded-xl text-xs transition flex items-center gap-2" 
                                        :class="activeTab === 'paket' ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'" 
                                        @click="activeTab = 'paket'">
                                    <i class="bi bi-collection text-sm"></i> Distribusi Buku Paket Kelas
                                </button>
                            </li>
                        </ul>
                    </div>

                    <button type="button" 
                            class="btn btn-sm btn-light border border-slate-200/80 rounded-xl shadow-2xs ms-1.5 hidden md:flex items-center justify-center shrink-0 text-slate-600 hover:text-blue-600 hover:bg-blue-50 transition w-[34px] h-[34px] z-5" 
                            onclick="document.getElementById('navTabsSirkulasi')?.scrollBy({ left: 220, behavior: 'smooth' })"
                            title="Geser ke Kanan">
                        <i class="bi bi-chevron-right"></i>
                    </button>
                </div>
            </div>

            <!-- Tab 1: Peminjaman Aktif -->
            <div v-if="activeTab === 'aktif'" class="space-y-4">
                <div class="flex flex-col md:flex-row items-center justify-between gap-3 bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs">
                    <div class="relative w-full md:w-80">
                        <i class="bi bi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                        <input v-model="searchQuery" type="text" placeholder="Cari peminjam, buku, kode..." 
                               class="w-full pl-9 pr-4 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-slate-50/50">
                    </div>
                    <div class="text-xs text-slate-500">
                        Menampilkan <strong class="text-slate-800">{{ filteredAktif.length }}</strong> transaksi aktif
                    </div>
                </div>

                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs text-slate-600">
                            <thead class="bg-slate-50/80 text-slate-700 font-semibold border-b border-slate-200/80 uppercase tracking-wider text-[11px]">
                                <tr>
                                    <th class="px-5 py-3.5">Kode / Peminjam</th>
                                    <th class="px-5 py-3.5">Informasi Buku</th>
                                    <th class="px-5 py-3.5">Tgl Pinjam & Jatuh Tempo</th>
                                    <th class="px-5 py-3.5">Status Keterlambatan</th>
                                    <th class="px-5 py-3.5 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="item in filteredAktif" :key="item.id" class="hover:bg-slate-50/60 transition">
                                    <td class="px-5 py-4">
                                        <div class="font-bold text-slate-800 text-sm">{{ item.peminjam_nama }}</div>
                                        <div class="text-slate-400 font-mono text-[11px] flex items-center gap-1.5 mt-0.5">
                                            <span class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-600 uppercase font-semibold text-[9px]">{{ item.tipe_peminjam }}</span>
                                            <span>{{ item.peminjam_identitas || '-' }}</span>
                                            <span>•</span>
                                            <span class="text-blue-600">{{ item.kode_transaksi }}</span>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4 max-w-xs">
                                        <div class="font-semibold text-slate-800 truncate" :title="item.judul_buku">{{ item.judul_buku }}</div>
                                        <div class="text-slate-400 text-[11px] flex items-center gap-2 mt-0.5">
                                            <span class="font-mono bg-blue-50 text-blue-700 px-1.5 py-0.5 rounded text-[10px] font-semibold">{{ item.kode_eksemplar || item.kode_buku }}</span>
                                            <span>{{ item.penulis || 'Anonim' }}</span>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4">
                                        <div class="flex items-center gap-1 text-slate-700">
                                            <i class="bi bi-calendar-event text-blue-500"></i> Pinjam: {{ formatDate(item.tanggal_pinjam) }}
                                        </div>
                                        <div class="flex items-center gap-1 font-semibold mt-0.5" :class="item.is_terlambat ? 'text-rose-600 font-bold' : 'text-slate-600'">
                                            <i class="bi bi-calendar-x" :class="item.is_terlambat ? 'text-rose-600' : 'text-slate-400'"></i> Tempo: {{ formatDate(item.tanggal_harus_kembali) }}
                                        </div>
                                    </td>
                                    <td class="px-5 py-4">
                                        <span v-if="item.is_terlambat" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-50 text-rose-600 border border-rose-200">
                                            <i class="bi bi-exclamation-triangle-fill"></i> Terlambat {{ item.terlambat_hari }} Hari
                                        </span>
                                        <span v-else class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-50 text-emerald-600 border border-emerald-200">
                                            <i class="bi bi-check-circle"></i> Sisa {{ item.sisa_hari }} Hari
                                        </span>
                                        <div v-if="item.perpanjangan_ke > 0" class="text-[10px] text-slate-400 mt-1">
                                            Diperpanjang {{ item.perpanjangan_ke }}x
                                        </div>
                                    </td>
                                    <td class="px-5 py-4 text-right">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <button @click="openModalKembali(item)" 
                                                    class="px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-xs shadow-2xs transition flex items-center gap-1"
                                                    title="Proses Pengembalian">
                                                <i class="bi bi-box-arrow-in-left"></i> Kembali
                                            </button>
                                            <button @click="perpanjangPinjaman(item)" 
                                                    class="px-3 py-1.5 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-600 font-medium text-xs border border-blue-200/60 transition flex items-center gap-1"
                                                    title="Perpanjang 7 Hari">
                                                <i class="bi bi-arrow-repeat"></i> Perpanjang
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="!filteredAktif.length">
                                    <td colspan="5" class="px-5 py-12 text-center text-slate-400">
                                        <i class="bi bi-journal-check text-3xl mb-2 block"></i>
                                        Tidak ada peminjaman buku yang sedang aktif saat ini.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Tab 2: Riwayat Selesai -->
            <div v-if="activeTab === 'selesai'" class="space-y-4">
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                    <div class="p-4 border-b border-slate-100 flex items-center justify-between">
                        <div class="text-sm font-bold text-slate-800">Log Transaksi Pengembalian Selesai</div>
                        <span class="text-xs text-slate-500">Total {{ props.sirkulasi_selesai?.length || 0 }} rekaman</span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs text-slate-600">
                            <thead class="bg-slate-50/80 text-slate-700 font-semibold border-b border-slate-200/80 uppercase tracking-wider text-[11px]">
                                <tr>
                                    <th class="px-5 py-3.5">Kode Transaksi</th>
                                    <th class="px-5 py-3.5">Peminjam</th>
                                    <th class="px-5 py-3.5">Judul Buku</th>
                                    <th class="px-5 py-3.5">Tgl Pinjam & Kembali</th>
                                    <th class="px-5 py-3.5">Kondisi / Denda</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="item in filteredSelesai" :key="item.id" class="hover:bg-slate-50/60">
                                    <td class="px-5 py-3.5 font-mono text-slate-700 font-semibold">{{ item.kode_transaksi }}</td>
                                    <td class="px-5 py-3.5">
                                        <div class="font-bold text-slate-800">{{ item.peminjam_nama }}</div>
                                        <div class="text-slate-400 text-[10px]">{{ item.peminjam_identitas || '-' }}</div>
                                    </td>
                                    <td class="px-5 py-3.5 font-medium text-slate-800">{{ item.judul_buku }}</td>
                                    <td class="px-5 py-3.5">
                                        <div>Pinjam: {{ formatDate(item.tanggal_pinjam) }}</div>
                                        <div class="text-emerald-600 font-medium">Kembali: {{ formatDate(item.tanggal_kembali_aktual) }}</div>
                                    </td>
                                    <td class="px-5 py-3.5">
                                        <span class="px-2 py-0.5 rounded text-[11px] font-semibold" 
                                              :class="item.kondisi_kembali === 'rusak' ? 'bg-amber-100 text-amber-800' : (item.kondisi_kembali === 'hilang' ? 'bg-rose-100 text-rose-800' : 'bg-emerald-100 text-emerald-800')">
                                            {{ item.kondisi_kembali || 'Baik' }}
                                        </span>
                                        <div v-if="item.total_denda > 0" class="text-[11px] text-rose-600 font-semibold mt-0.5">
                                            Denda: {{ formatCurrency(item.total_denda) }}
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="!filteredSelesai.length">
                                    <td colspan="5" class="px-5 py-8 text-center text-slate-400">Belum ada riwayat transaksi selesai.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Tab 3: Rekap Denda -->
            <div v-if="activeTab === 'denda'" class="space-y-4">
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                    <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                        <div>
                            <h3 class="text-base font-bold text-slate-800">Daftar Tagihan Denda Keterlambatan / Kerusakan</h3>
                            <p class="text-xs text-slate-500 mt-0.5">Peminjam yang memiliki tanggungan denda perpustakaan yang belum lunas.</p>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs text-slate-600">
                            <thead class="bg-slate-50/80 text-slate-700 font-semibold border-b border-slate-200/80 uppercase tracking-wider text-[11px]">
                                <tr>
                                    <th class="px-5 py-3.5">Peminjam & Identitas</th>
                                    <th class="px-5 py-3.5">Judul Buku Terkait</th>
                                    <th class="px-5 py-3.5">Total Denda</th>
                                    <th class="px-5 py-3.5">Terbayar</th>
                                    <th class="px-5 py-3.5">Sisa Tagihan</th>
                                    <th class="px-5 py-3.5 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="d in props.denda_list" :key="d.id" class="hover:bg-slate-50/60">
                                    <td class="px-5 py-4">
                                        <div class="font-bold text-slate-800">{{ d.peminjam_nama }}</div>
                                        <div class="text-slate-400 font-mono text-[11px]">{{ d.peminjam_identitas || '-' }} ({{ d.tipe_peminjam }})</div>
                                    </td>
                                    <td class="px-5 py-4 font-medium text-slate-800">{{ d.judul_buku }}</td>
                                    <td class="px-5 py-4 font-semibold text-slate-700">{{ formatCurrency(d.total_denda) }}</td>
                                    <td class="px-5 py-4 text-emerald-600 font-semibold">{{ formatCurrency(d.denda_dibayar) }}</td>
                                    <td class="px-5 py-4 font-bold text-rose-600">{{ formatCurrency((d.total_denda || 0) - (d.denda_dibayar || 0)) }}</td>
                                    <td class="px-5 py-4 text-right">
                                        <button @click="openModalBayarDenda(d)" 
                                                class="px-3 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-medium text-xs transition flex items-center gap-1 ms-auto">
                                            <i class="bi bi-wallet2"></i> Bayar / Lunasi
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="!props.denda_list?.length">
                                    <td colspan="6" class="px-5 py-8 text-center text-slate-400">
                                        <i class="bi bi-check-circle-fill text-emerald-500 text-2xl block mb-1"></i>
                                        Tidak ada tanggungan denda aktif. Semua peminjam bebas dari tagihan denda.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Tab 4: Distribusi Buku Paket Kelas -->
            <div v-if="activeTab === 'paket'" class="space-y-4">
                <div class="flex items-center justify-between bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs">
                    <div>
                        <h3 class="text-base font-bold text-slate-800">Distribusi Buku Pelajaran Paket per Rombel / Kelas</h3>
                        <p class="text-xs text-slate-500">Peminjaman massal buku pelajaran kurikulum per semester.</p>
                    </div>
                    <button @click="isModalPaketOpen = true" class="px-3.5 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-medium text-xs transition flex items-center gap-1.5">
                        <i class="bi bi-plus-lg"></i> Buat Paket Baru
                    </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div v-for="paket in props.paket_list" :key="paket.id" class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <span class="px-2 py-0.5 rounded bg-blue-50 text-blue-700 font-mono text-xs font-bold">{{ paket.kode_paket }}</span>
                                <span class="text-xs font-semibold px-2 py-0.5 rounded-full" :class="paket.is_active ? 'bg-emerald-50 text-emerald-600' : 'bg-slate-100 text-slate-500'">
                                    {{ paket.is_active ? 'Aktif' : 'Selesai' }}
                                </span>
                            </div>
                            <h4 class="font-bold text-slate-800 text-sm mb-1">{{ paket.nama_paket }}</h4>
                            <div class="text-xs text-slate-500 space-y-1 mt-3">
                                <div><i class="bi bi-mortarboard me-1.5 text-slate-400"></i> Kelas: <strong class="text-slate-700">{{ paket.kelas?.nama_kelas || '-' }}</strong></div>
                                <div><i class="bi bi-book me-1.5 text-slate-400"></i> Mapel: <strong class="text-slate-700">{{ paket.mata_pelajaran || '-' }}</strong></div>
                                <div><i class="bi bi-calendar3 me-1.5 text-slate-400"></i> TA / Sem: {{ paket.tahun_ajaran }} (Sem {{ paket.semester }})</div>
                                <div><i class="bi bi-boxes me-1.5 text-slate-400"></i> Jumlah: {{ paket.jumlah_eksemplar }} Eks</div>
                            </div>
                        </div>
                    </div>
                    <div v-if="!props.paket_list?.length" class="col-span-3 bg-white p-8 rounded-2xl border border-dashed border-slate-300 text-center text-slate-400">
                        Belum ada distribusi paket buku kelas. Klik tombol "Buat Paket Baru" untuk menambahkan alokasi.
                    </div>
                </div>
            </div>
        </div>

        <!-- TELEPORT MODAL PINJAM BUKU -->
        <Teleport to="body">
            <div v-if="isModalPinjamOpen" class="fixed inset-0 z-[9999] flex items-center justify-center overflow-y-auto p-4 sm:p-6">
                <div class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm transition-opacity" @click="isModalPinjamOpen = false"></div>
                <div class="relative w-full max-w-2xl bg-white rounded-2xl shadow-2xl border border-slate-100 overflow-hidden transform transition-all">
                    <div class="p-6 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                        <div class="flex items-center gap-2.5">
                            <div class="w-9 h-9 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center text-lg font-bold">
                                <i class="bi bi-box-arrow-up-right"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-slate-800 text-base">Kios Transaksi Peminjaman Buku</h3>
                                <p class="text-xs text-slate-500">Form input transaksi peminjaman pustaka.</p>
                            </div>
                        </div>
                        <button @click="isModalPinjamOpen = false" class="text-slate-400 hover:text-slate-600 transition">
                            <i class="bi bi-x-lg text-lg"></i>
                        </button>
                    </div>

                    <form @submit.prevent="submitPinjam" class="p-6 space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Pilih Anggota / Peminjam *</label>
                                <select @change="onSelectMember" class="w-full text-xs rounded-xl border border-slate-200 py-2.5 px-3 focus:ring-2 focus:ring-blue-500">
                                    <option value="">-- Pilih dari Daftar Terdaftar --</option>
                                    <option v-for="m in props.member_list" :key="m.id" :value="m.id">
                                        [{{ m.tipe }}] {{ m.nama }} ({{ m.nomor_identitas || '-' }})
                                    </option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Peminjam (Otomatis/Manual) *</label>
                                <input v-model="formPinjam.peminjam_nama" type="text" required class="w-full text-xs rounded-xl border border-slate-200 py-2.5 px-3" placeholder="Nama lengkap...">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">NIS / NIP / No. Identitas</label>
                                <input v-model="formPinjam.peminjam_identitas" type="text" class="w-full text-xs rounded-xl border border-slate-200 py-2.5 px-3" placeholder="Nomor identitas...">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Tipe Anggota</label>
                                <select v-model="formPinjam.tipe_peminjam" class="w-full text-xs rounded-xl border border-slate-200 py-2.5 px-3">
                                    <option value="siswa">Siswa</option>
                                    <option value="guru">Guru / Pendidik</option>
                                    <option value="staff">Staff / Tenaga Kependidikan</option>
                                    <option value="umum">Umum / Luar</option>
                                </select>
                            </div>
                        </div>

                        <div class="border-t border-slate-100 pt-4">
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Pilih Judul Buku *</label>
                            <select @change="onSelectBuku" class="w-full text-xs rounded-xl border border-slate-200 py-2.5 px-3 focus:ring-2 focus:ring-blue-500">
                                <option value="">-- Pilih Buku dari Katalog --</option>
                                <option v-for="b in props.buku_list" :key="b.id" :value="b.id">
                                    {{ b.judul }} (Tersedia: {{ b.jumlah_tersedia }} eks)
                                </option>
                            </select>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Barcode / Kode Eksemplar</label>
                                <input v-model="formPinjam.kode_eksemplar" type="text" class="w-full text-xs rounded-xl border border-slate-200 py-2.5 px-3" placeholder="Contoh: BUK-001-01">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Durasi Pinjam (Hari)</label>
                                <input v-model.number="formPinjam.lama_hari" type="number" min="1" max="30" class="w-full text-xs rounded-xl border border-slate-200 py-2.5 px-3">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Catatan Tambahan</label>
                            <input v-model="formPinjam.keterangan" type="text" class="w-full text-xs rounded-xl border border-slate-200 py-2.5 px-3" placeholder="Opsional...">
                        </div>

                        <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-2">
                            <button type="button" @click="isModalPinjamOpen = false" class="px-4 py-2.5 rounded-xl border border-slate-200 text-slate-600 font-medium text-xs hover:bg-slate-50 transition">
                                Batal
                            </button>
                            <button type="submit" :disabled="formPinjam.processing" class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-medium text-xs shadow transition flex items-center gap-1.5">
                                <i class="bi bi-check2"></i> Simpan Transaksi Peminjaman
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- TELEPORT MODAL PENGEMBALIAN BUKU -->
        <Teleport to="body">
            <div v-if="isModalKembaliOpen" class="fixed inset-0 z-[9999] flex items-center justify-center overflow-y-auto p-4 sm:p-6">
                <div class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm transition-opacity" @click="isModalKembaliOpen = false"></div>
                <div class="relative w-full max-w-lg bg-white rounded-2xl shadow-2xl border border-slate-100 overflow-hidden transform transition-all">
                    <div class="p-6 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                        <div class="flex items-center gap-2.5">
                            <div class="w-9 h-9 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center text-lg font-bold">
                                <i class="bi bi-box-arrow-in-left"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-slate-800 text-base">Proses Pengembalian Pustaka</h3>
                                <p class="text-xs text-slate-500">Periksa kondisi fisik dan kalkulasi denda.</p>
                            </div>
                        </div>
                        <button @click="isModalKembaliOpen = false" class="text-slate-400 hover:text-slate-600 transition">
                            <i class="bi bi-x-lg text-lg"></i>
                        </button>
                    </div>

                    <form @submit.prevent="submitKembali" class="p-6 space-y-4">
                        <div v-if="selectedSirkulasi" class="p-3.5 rounded-xl bg-slate-50 border border-slate-200/80 text-xs space-y-1">
                            <div>Peminjam: <strong class="text-slate-800">{{ selectedSirkulasi.peminjam_nama }}</strong></div>
                            <div>Buku: <strong class="text-slate-800">{{ selectedSirkulasi.judul_buku }}</strong></div>
                            <div>Jatuh Tempo: {{ formatDate(selectedSirkulasi.tanggal_harus_kembali) }}</div>
                            <div v-if="selectedSirkulasi.is_terlambat" class="text-rose-600 font-bold">
                                Terlambat: {{ selectedSirkulasi.terlambat_hari }} Hari
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Kondisi Fisik Buku Saat Kembali *</label>
                            <select v-model="formKembali.kondisi_kembali" class="w-full text-xs rounded-xl border border-slate-200 py-2.5 px-3">
                                <option value="baik">Kondisi Baik / Sempurna</option>
                                <option value="rusak_ringan">Rusak Ringan</option>
                                <option value="rusak_berat">Rusak Berat</option>
                                <option value="hilang">Buku Hilang</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Nominal Denda (Rp)</label>
                            <input v-model.number="formKembali.denda" type="number" min="0" class="w-full text-xs rounded-xl border border-slate-200 py-2.5 px-3 font-semibold text-rose-600">
                            <span class="text-[10px] text-slate-400 mt-0.5 block">Otomatis dihitung Rp 1.000 / hari jika terlambat.</span>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Catatan Pengembalian</label>
                            <input v-model="formKembali.keterangan_kembali" type="text" class="w-full text-xs rounded-xl border border-slate-200 py-2.5 px-3" placeholder="Opsional...">
                        </div>

                        <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-2">
                            <button type="button" @click="isModalKembaliOpen = false" class="px-4 py-2.5 rounded-xl border border-slate-200 text-slate-600 font-medium text-xs hover:bg-slate-50 transition">
                                Batal
                            </button>
                            <button type="submit" :disabled="formKembali.processing" class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-xs shadow transition flex items-center gap-1.5">
                                <i class="bi bi-check2-circle"></i> Konfirmasi Pengembalian
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- TELEPORT MODAL BAYAR DENDA -->
        <Teleport to="body">
            <div v-if="isModalBayarDendaOpen" class="fixed inset-0 z-[9999] flex items-center justify-center overflow-y-auto p-4 sm:p-6">
                <div class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm transition-opacity" @click="isModalBayarDendaOpen = false"></div>
                <div class="relative w-full max-w-md bg-white rounded-2xl shadow-2xl border border-slate-100 overflow-hidden transform transition-all">
                    <div class="p-6 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                        <div class="flex items-center gap-2.5">
                            <div class="w-9 h-9 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center text-lg font-bold">
                                <i class="bi bi-wallet2"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-slate-800 text-base">Pelunasan Denda Perpustakaan</h3>
                                <p class="text-xs text-slate-500">Penerimaan kas pembayaran denda.</p>
                            </div>
                        </div>
                        <button @click="isModalBayarDendaOpen = false" class="text-slate-400 hover:text-slate-600 transition">
                            <i class="bi bi-x-lg text-lg"></i>
                        </button>
                    </div>

                    <form @submit.prevent="submitBayarDenda" class="p-6 space-y-4">
                        <div v-if="selectedDenda" class="p-3.5 rounded-xl bg-slate-50 border border-slate-200/80 text-xs space-y-1">
                            <div>Peminjam: <strong class="text-slate-800">{{ selectedDenda.peminjam_nama }}</strong></div>
                            <div>Total Denda: <strong class="text-rose-600">{{ formatCurrency(selectedDenda.total_denda) }}</strong></div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Jumlah Pembayaran (Rp) *</label>
                            <input v-model.number="formBayarDenda.jumlah_bayar" type="number" min="1" required class="w-full text-xs rounded-xl border border-slate-200 py-2.5 px-3 font-bold text-emerald-600">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Metode Pembayaran</label>
                            <select v-model="formBayarDenda.metode_bayar" class="w-full text-xs rounded-xl border border-slate-200 py-2.5 px-3">
                                <option value="tunai">Tunai / Cash di Meja Sirkulasi</option>
                                <option value="transfer">Transfer Bank / QRIS</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Catatan / Bukti Kas</label>
                            <input v-model="formBayarDenda.catatan_bayar" type="text" class="w-full text-xs rounded-xl border border-slate-200 py-2.5 px-3" placeholder="No. kwitansi / catatan...">
                        </div>

                        <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-2">
                            <button type="button" @click="isModalBayarDendaOpen = false" class="px-4 py-2.5 rounded-xl border border-slate-200 text-slate-600 font-medium text-xs hover:bg-slate-50 transition">
                                Batal
                            </button>
                            <button type="submit" :disabled="formBayarDenda.processing" class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-medium text-xs shadow transition flex items-center gap-1.5">
                                <i class="bi bi-cash-stack"></i> Simpan Pembayaran
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- TELEPORT MODAL PAKET BUKU -->
        <Teleport to="body">
            <div v-if="isModalPaketOpen" class="fixed inset-0 z-[9999] flex items-center justify-center overflow-y-auto p-4 sm:p-6">
                <div class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm transition-opacity" @click="isModalPaketOpen = false"></div>
                <div class="relative w-full max-w-lg bg-white rounded-2xl shadow-2xl border border-slate-100 overflow-hidden transform transition-all">
                    <div class="p-6 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                        <div class="flex items-center gap-2.5">
                            <div class="w-9 h-9 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center text-lg font-bold">
                                <i class="bi bi-collection"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-slate-800 text-base">Distribusi Buku Paket Pelajaran</h3>
                                <p class="text-xs text-slate-500">Alokasikan paket buku untuk rombel / kelas.</p>
                            </div>
                        </div>
                        <button @click="isModalPaketOpen = false" class="text-slate-400 hover:text-slate-600 transition">
                            <i class="bi bi-x-lg text-lg"></i>
                        </button>
                    </div>

                    <form @submit.prevent="submitPaket" class="p-6 space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Paket Buku *</label>
                            <input v-model="formPaket.nama_paket" type="text" required class="w-full text-xs rounded-xl border border-slate-200 py-2.5 px-3" placeholder="Contoh: Paket Buku Tematik Kelas VII">
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Target Kelas / Rombel</label>
                                <select v-model="formPaket.kelas_id" class="w-full text-xs rounded-xl border border-slate-200 py-2.5 px-3">
                                    <option value="">-- Pilih Kelas --</option>
                                    <option v-for="k in props.kelas_list" :key="k.id" :value="k.id">{{ k.nama_kelas }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Mata Pelajaran</label>
                                <input v-model="formPaket.mata_pelajaran" type="text" class="w-full text-xs rounded-xl border border-slate-200 py-2.5 px-3" placeholder="Matematika, IPA...">
                            </div>
                        </div>

                        <div class="grid grid-cols-3 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Tahun Ajaran</label>
                                <input v-model="formPaket.tahun_ajaran" type="text" class="w-full text-xs rounded-xl border border-slate-200 py-2 px-3">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Semester</label>
                                <select v-model="formPaket.semester" class="w-full text-xs rounded-xl border border-slate-200 py-2 px-3">
                                    <option value="1">Ganjil (1)</option>
                                    <option value="2">Genap (2)</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Jumlah Eks</label>
                                <input v-model.number="formPaket.jumlah_eksemplar" type="number" min="1" class="w-full text-xs rounded-xl border border-slate-200 py-2 px-3">
                            </div>
                        </div>

                        <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-2">
                            <button type="button" @click="isModalPaketOpen = false" class="px-4 py-2.5 rounded-xl border border-slate-200 text-slate-600 font-medium text-xs hover:bg-slate-50 transition">
                                Batal
                            </button>
                            <button type="submit" :disabled="formPaket.processing" class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-medium text-xs shadow transition flex items-center gap-1.5">
                                <i class="bi bi-check2"></i> Simpan Distribusi Paket
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>
