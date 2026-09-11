<script setup>
import { ref, computed } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link, router } from '@inertiajs/vue3'

const props = defineProps({
    bukuList: Object,
    koleksi: Object,
    kategori_list: Array,
    pengaturan: Object,
    stats: Object,
    tenants: Array,
    isSuperAdmin: Boolean,
    activeTenantId: String,
    filters: Object,
})

const searchQuery = ref(props.filters?.q || props.filters?.search || '')
const selectedKategori = ref(props.filters?.kategori || '')
const selectedTenantId = ref(props.filters?.tenant_id || '')
const viewMode = ref('grid') // 'grid' | 'table'

const getSelectedTenantName = () => {
    if (!selectedTenantId.value) return 'Semua Sekolah (Agregat Global)'
    const found = props.tenants?.find(t => t.id === selectedTenantId.value)
    return found ? `${found.nama_sekolah} (${found.npsn})` : 'Sekolah Terpilih'
}

const applyTenantFilter = () => {
    router.get('/perpustakaan/opac', {
        q: searchQuery.value || undefined,
        kategori: selectedKategori.value || undefined,
        tenant_id: selectedTenantId.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    })
}

// Normalized book collection
const bookCollection = computed(() => {
    return props.bukuList || props.koleksi || { data: [] }
})

const categoryOptions = computed(() => {
    if (props.kategori_list?.length) return props.kategori_list
    return ['Teknologi', 'Sains', 'Matematika', 'Sastra & Bahasa', 'Sosial & Sejarah', 'Agama & Moral', 'Seni & Olahraga', 'Fiksi / Novel']
})

// E-Book & Detail Modal
const isModalDetailOpen = ref(false)
const selectedBuku = ref(null)

const openDetail = (buku) => {
    selectedBuku.value = buku
    isModalDetailOpen.value = true
}

const search = () => {
    router.get('/perpustakaan/opac', {
        q: searchQuery.value,
        search: searchQuery.value,
        kategori: selectedKategori.value,
        tenant_id: selectedTenantId.value || undefined,
    }, {
        preserveState: true,
        replace: true,
    })
}

const selectKategori = (kat) => {
    selectedKategori.value = selectedKategori.value === kat ? '' : kat
    search()
}
</script>

<template>
    <AppLayout title="OPAC - Online Public Access Catalog">
        <div class="space-y-6">
            <!-- Hero Search Header -->
            <div class="relative bg-gradient-to-r from-slate-900 via-blue-900 to-indigo-950 rounded-3xl p-8 md:p-12 text-white shadow-xl overflow-hidden">
                <!-- Background Glowing Circles -->
                <div class="absolute -top-24 -right-24 w-96 h-96 bg-blue-500/20 rounded-full blur-3xl pointer-events-none"></div>
                <div class="absolute -bottom-24 -left-24 w-96 h-96 bg-indigo-500/20 rounded-full blur-3xl pointer-events-none"></div>

                <div class="relative z-10 max-w-3xl mx-auto text-center space-y-4">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 backdrop-blur-md border border-white/20 text-xs font-semibold text-blue-200">
                        <i class="bi bi-search-heart text-blue-400"></i> Katalog Akses Publik Daring (OPAC)
                    </div>
                    <h1 class="text-3xl md:text-4xl font-extrabold tracking-tight">
                        Temukan Pengetahuan di <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-300 to-cyan-200">{{ props.pengaturan?.nama_perpustakaan || 'Perpustakaan Digital' }}</span>
                    </h1>
                    <p class="text-sm md:text-base text-slate-300">
                        Eksplorasi ribuan judul buku cetak, e-book multimedia, jurnal berkala, dan koleksi referensi secara cepat dan akurat.
                    </p>

                    <!-- Big Search Bar -->
                    <form @submit.prevent="search" class="pt-4 flex items-center max-w-2xl mx-auto">
                        <div class="relative w-full">
                            <i class="bi bi-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-lg"></i>
                            <input v-model="searchQuery" type="text" placeholder="Ketik judul buku, nama penulis, ISBN, atau topik materi..." 
                                   class="w-full pl-12 pr-28 py-3.5 text-sm rounded-2xl bg-white text-slate-800 placeholder-slate-400 shadow-2xl focus:outline-none focus:ring-4 focus:ring-blue-400/50">
                            <button type="submit" class="absolute right-2 top-1/2 -translate-y-1/2 px-5 py-2.5 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-semibold text-xs shadow transition">
                                Cari Pustaka
                            </button>
                        </div>
                    </form>
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
                                Menampilkan koleksi OPAC milik: <strong class="text-blue-700 font-bold ml-1">{{ getSelectedTenantName() }}</strong>
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

            <!-- Category Fast Filter Scroller -->
            <div class="bg-white rounded-2xl p-3 border border-slate-200/80 shadow-2xs">
                <div class="flex items-center gap-2 overflow-x-auto no-scrollbar py-1">
                    <button @click="selectKategori('')" 
                            class="px-4 py-2 rounded-xl text-xs font-semibold whitespace-nowrap transition"
                            :class="!selectedKategori ? 'bg-blue-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'">
                        <i class="bi bi-grid me-1"></i> Semua Kategori
                    </button>
                    <button v-for="kat in categoryOptions" :key="kat" 
                            @click="selectKategori(kat)"
                            class="px-4 py-2 rounded-xl text-xs font-semibold whitespace-nowrap transition"
                            :class="selectedKategori === kat ? 'bg-blue-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'">
                        {{ kat }}
                    </button>
                </div>
            </div>

            <!-- Content Grid / Catalog -->
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <div class="text-xs text-slate-500 font-medium">
                        Menampilkan katalog pustaka publik (Total: <strong>{{ props.koleksi?.total || props.koleksi?.data?.length || 0 }}</strong> judul)
                    </div>
                    <div class="flex items-center gap-1 bg-white p-1 rounded-xl border border-slate-200/80">
                        <button @click="viewMode = 'grid'" class="p-1.5 rounded-lg text-xs" :class="viewMode === 'grid' ? 'bg-blue-50 text-blue-600 font-bold' : 'text-slate-400 hover:text-slate-600'">
                            <i class="bi bi-grid-fill"></i>
                        </button>
                        <button @click="viewMode = 'table'" class="p-1.5 rounded-lg text-xs" :class="viewMode === 'table' ? 'bg-blue-50 text-blue-600 font-bold' : 'text-slate-400 hover:text-slate-600'">
                            <i class="bi bi-list-ul"></i>
                        </button>
                    </div>
                </div>

                <!-- Grid Cards View -->
                <div v-if="viewMode === 'grid'" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
                    <div v-for="buku in props.koleksi?.data" :key="buku.id" 
                         @click="openDetail(buku)"
                         class="bg-white rounded-2xl border border-slate-200/80 shadow-2xs hover:shadow-md hover:border-blue-300 transition cursor-pointer overflow-hidden flex flex-col justify-between group">
                        <div>
                            <!-- Book Cover Preview -->
                            <div class="h-44 bg-gradient-to-br from-slate-100 to-blue-50 relative flex items-center justify-center overflow-hidden border-b border-slate-100">
                                <img v-if="buku.cover_buku" :src="buku.cover_buku" :alt="buku.judul" class="h-full w-full object-cover group-hover:scale-105 transition duration-300">
                                <div v-else class="text-center p-4">
                                    <i class="bi bi-journal-text text-4xl text-blue-300 group-hover:scale-110 transition duration-300 block mb-1"></i>
                                    <span class="text-[10px] font-mono text-slate-400">{{ buku.kode_buku }}</span>
                                </div>
                                <span v-if="buku.is_ebook" class="absolute top-2.5 right-2.5 px-2 py-0.5 rounded-full bg-indigo-600/90 backdrop-blur-md text-white font-bold text-[9px] shadow-xs">
                                    <i class="bi bi-file-earmark-pdf"></i> E-Book
                                </span>
                            </div>

                            <div class="p-4 space-y-2">
                                <div class="text-[10px] font-semibold text-blue-600 uppercase tracking-wider">{{ buku.kategori || 'Umum' }}</div>
                                <h3 class="font-bold text-slate-800 text-xs line-clamp-2 leading-snug group-hover:text-blue-600 transition" :title="buku.judul">
                                    {{ buku.judul }}
                                </h3>
                                <div class="text-[11px] text-slate-500 truncate"><i class="bi bi-person me-1"></i> {{ buku.penulis || 'Anonim' }}</div>
                            </div>
                        </div>

                        <div class="p-4 pt-0 border-t border-slate-50 mt-2 flex items-center justify-between text-[11px]">
                            <span class="text-slate-400 font-mono text-[10px]">DDC: {{ buku.nomor_klasifikasi_ddc || '-' }}</span>
                            <span class="px-2 py-0.5 rounded-full font-semibold text-[10px]" 
                                  :class="buku.jumlah_tersedia > 0 ? 'bg-emerald-50 text-emerald-600' : 'bg-rose-50 text-rose-600'">
                                {{ buku.jumlah_tersedia > 0 ? `Tersedia (${buku.jumlah_tersedia})` : 'Habis' }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Table View -->
                <div v-if="viewMode === 'table'" class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs text-slate-600">
                            <thead class="bg-slate-50/80 text-slate-700 font-semibold border-b border-slate-200/80 uppercase tracking-wider text-[11px]">
                                <tr>
                                    <th class="px-5 py-3.5">Kode / Klasifikasi DDC</th>
                                    <th class="px-5 py-3.5">Judul & Penulis</th>
                                    <th class="px-5 py-3.5">Penerbit & Tahun</th>
                                    <th class="px-5 py-3.5">Status Ketersediaan</th>
                                    <th class="px-5 py-3.5 text-right">Detail</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="buku in props.koleksi?.data" :key="buku.id" class="hover:bg-slate-50/60">
                                    <td class="px-5 py-3.5">
                                        <div class="font-mono text-blue-600 font-bold">{{ buku.kode_buku }}</div>
                                        <div class="text-[10px] text-slate-400">DDC: {{ buku.nomor_klasifikasi_ddc || '-' }}</div>
                                    </td>
                                    <td class="px-5 py-3.5">
                                        <div class="font-bold text-slate-800">{{ buku.judul }}</div>
                                        <div class="text-slate-400 text-[11px]">{{ buku.penulis || 'Anonim' }}</div>
                                    </td>
                                    <td class="px-5 py-3.5">
                                        <div>{{ buku.penerbit || '-' }}</div>
                                        <div class="text-slate-400 text-[10px]">Tahun {{ buku.tahun_terbit || '-' }}</div>
                                    </td>
                                    <td class="px-5 py-3.5">
                                        <span class="px-2.5 py-1 rounded-full text-xs font-semibold" 
                                              :class="buku.jumlah_tersedia > 0 ? 'bg-emerald-50 text-emerald-600' : 'bg-rose-50 text-rose-600'">
                                            {{ buku.jumlah_tersedia > 0 ? `${buku.jumlah_tersedia} Tersedia` : 'Sedang Dipinjam Semua' }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-3.5 text-right">
                                        <button @click="openDetail(buku)" class="px-3 py-1.5 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 font-medium text-xs transition">
                                            Lihat Info
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div v-if="!props.koleksi?.data?.length" class="bg-white p-12 rounded-2xl border border-dashed border-slate-300 text-center text-slate-400">
                    <i class="bi bi-search text-4xl mb-2 block"></i>
                    Tidak ada pustaka yang sesuai dengan kata kunci pencarian Anda.
                </div>
            </div>
        </div>

        <!-- TELEPORT MODAL DETAIL PUSTAKA & E-BOOK -->
        <Teleport to="body">
            <div v-if="isModalDetailOpen" class="fixed inset-0 z-[9999] flex items-center justify-center overflow-y-auto p-4 sm:p-6">
                <div class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm transition-opacity" @click="isModalDetailOpen = false"></div>
                <div class="relative w-full max-w-2xl bg-white rounded-2xl shadow-2xl border border-slate-100 overflow-hidden transform transition-all">
                    <div class="p-6 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                        <div class="flex items-center gap-2.5">
                            <div class="w-9 h-9 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center text-lg font-bold">
                                <i class="bi bi-book"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-slate-800 text-base">Detail Bibliografi Pustaka</h3>
                                <p class="text-xs text-slate-500">Informasi katalog & lokasi penempatan rak.</p>
                            </div>
                        </div>
                        <button @click="isModalDetailOpen = false" class="text-slate-400 hover:text-slate-600 transition">
                            <i class="bi bi-x-lg text-lg"></i>
                        </button>
                    </div>

                    <div v-if="selectedBuku" class="p-6 space-y-5">
                        <div class="flex flex-col md:flex-row gap-5">
                            <div class="w-full md:w-36 h-48 rounded-xl bg-slate-100 flex items-center justify-center overflow-hidden border border-slate-200 shrink-0">
                                <img v-if="selectedBuku.cover_buku" :src="selectedBuku.cover_buku" class="h-full w-full object-cover">
                                <i v-else class="bi bi-journal-text text-5xl text-slate-300"></i>
                            </div>
                            <div class="space-y-2 grow">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-blue-700 uppercase">{{ selectedBuku.kategori || 'Umum' }}</span>
                                <h2 class="text-lg font-bold text-slate-800 leading-snug">{{ selectedBuku.judul }}</h2>
                                <div class="text-xs text-slate-600 space-y-1">
                                    <div>Penulis: <strong class="text-slate-800">{{ selectedBuku.penulis || 'Anonim' }}</strong></div>
                                    <div>Penerbit: {{ selectedBuku.penerbit || '-' }} (Tahun {{ selectedBuku.tahun_terbit || '-' }})</div>
                                    <div>ISBN: <span class="font-mono">{{ selectedBuku.isbn || '-' }}</span></div>
                                    <div>Lokasi Rak: <strong class="text-blue-600">{{ selectedBuku.lokasi_rak || '-' }}</strong></div>
                                </div>
                            </div>
                        </div>

                        <div v-if="selectedBuku.deskripsi" class="p-4 rounded-xl bg-slate-50 border border-slate-100 text-xs text-slate-600">
                            <div class="font-bold text-slate-700 mb-1">Sinopsis / Abstrak:</div>
                            <p class="leading-relaxed">{{ selectedBuku.deskripsi }}</p>
                        </div>

                        <div class="grid grid-cols-3 gap-3 p-4 rounded-xl bg-slate-50 border border-slate-100 text-center text-xs">
                            <div>
                                <div class="text-slate-400">Total Stok</div>
                                <div class="text-base font-bold text-slate-800">{{ selectedBuku.jumlah_total || 0 }} Eks</div>
                            </div>
                            <div>
                                <div class="text-slate-400">Sedang Dipinjam</div>
                                <div class="text-base font-bold text-amber-600">{{ (selectedBuku.jumlah_total || 0) - (selectedBuku.jumlah_tersedia || 0) }} Eks</div>
                            </div>
                            <div>
                                <div class="text-slate-400">Tersedia di Rak</div>
                                <div class="text-base font-bold text-emerald-600">{{ selectedBuku.jumlah_tersedia || 0 }} Eks</div>
                            </div>
                        </div>

                        <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
                            <div>
                                <a v-if="selectedBuku.file_ebook" :href="selectedBuku.file_ebook" target="_blank" 
                                   class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs transition inline-flex items-center gap-1.5">
                                    <i class="bi bi-file-earmark-pdf"></i> Baca E-Book Digital
                                </a>
                            </div>
                            <button type="button" @click="isModalDetailOpen = false" class="px-5 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium text-xs transition">
                                Tutup
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>
