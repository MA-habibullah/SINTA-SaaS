<script setup>
import { ref } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link } from '@inertiajs/vue3'

const props = defineProps({
    sirkulasi_aktif: Array,
    sirkulasi_selesai: Array,
    total_denda_pending: Number,
    is_bebas_pustaka: Boolean,
    member_info: Object,
    pengaturan: Object,
})

const activeTab = ref('aktif')

const formatDate = (dateStr) => {
    if (!dateStr) return '-'
    const d = new Date(dateStr)
    return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })
}

const formatCurrency = (val) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val || 0)
}

// Modal Surat Bebas Pustaka
const isModalSuratOpen = ref(false)
const cetakSurat = () => {
    window.print()
}
</script>

<template>
    <AppLayout title="Riwayat Pustaka Saya">
        <div class="space-y-6">
            <!-- Header User Card -->
            <div class="bg-gradient-to-r from-slate-900 via-blue-900 to-indigo-950 rounded-3xl p-6 md:p-8 text-white shadow-xl relative overflow-hidden">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 relative z-10">
                    <div class="flex items-center gap-4">
                        <div class="w-16 h-16 rounded-2xl bg-white/10 backdrop-blur-md border border-white/20 flex items-center justify-center text-3xl text-blue-300 shrink-0">
                            <i class="bi bi-person-bounding-box"></i>
                        </div>
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase bg-blue-500/30 text-blue-200 border border-blue-400/30">
                                    {{ props.member_info?.tipe || 'Pemustaka' }}
                                </span>
                                <span class="text-xs text-blue-200/60">•</span>
                                <span class="text-xs text-blue-200 font-mono">No: {{ props.member_info?.nomor_anggota }}</span>
                            </div>
                            <h1 class="text-2xl font-extrabold tracking-tight">{{ props.member_info?.nama }}</h1>
                            <p class="text-xs text-slate-300 mt-0.5">NIS/NIP: {{ props.member_info?.nomor_identitas || '-' }}</p>
                        </div>
                    </div>

                    <!-- Status Bebas Pustaka Badge & Action -->
                    <div class="flex items-center gap-3">
                        <div v-if="props.is_bebas_pustaka" class="p-3 rounded-2xl bg-emerald-500/20 border border-emerald-400/30 backdrop-blur-md flex items-center gap-3">
                            <i class="bi bi-shield-fill-check text-2xl text-emerald-400"></i>
                            <div>
                                <div class="text-xs font-bold text-emerald-300 uppercase">Status Bebas Pustaka</div>
                                <div class="text-[11px] text-emerald-100">Tidak ada tanggungan pinjaman/denda</div>
                            </div>
                            <button @click="isModalSuratOpen = true" class="px-3 py-1.5 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white font-semibold text-xs shadow transition ms-2">
                                Unduh Surat
                            </button>
                        </div>
                        <div v-else class="p-3 rounded-2xl bg-rose-500/20 border border-rose-400/30 backdrop-blur-md flex items-center gap-3">
                            <i class="bi bi-exclamation-triangle-fill text-2xl text-rose-400"></i>
                            <div>
                                <div class="text-xs font-bold text-rose-300 uppercase">Ada Tanggungan</div>
                                <div class="text-[11px] text-rose-100">
                                    {{ props.sirkulasi_aktif?.length || 0 }} Buku Dipinjam • Denda: {{ formatCurrency(props.total_denda_pending) }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats Bar -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-2xs flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl shrink-0">
                        <i class="bi bi-book"></i>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-slate-800">{{ props.sirkulasi_aktif?.length || 0 }}</div>
                        <div class="text-xs text-slate-500 font-medium">Buku Sedang Dipinjam</div>
                    </div>
                </div>
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-2xs flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-xl shrink-0">
                        <i class="bi bi-journal-check"></i>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-slate-800">{{ props.sirkulasi_selesai?.length || 0 }}</div>
                        <div class="text-xs text-slate-500 font-medium">Total Riwayat Selesai</div>
                    </div>
                </div>
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-2xs flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center text-xl shrink-0">
                        <i class="bi bi-cash-stack"></i>
                    </div>
                    <div>
                        <div class="text-2xl font-bold" :class="props.total_denda_pending > 0 ? 'text-rose-600' : 'text-slate-800'">
                            {{ formatCurrency(props.total_denda_pending) }}
                        </div>
                        <div class="text-xs text-slate-500 font-medium">Tanggungan Denda</div>
                    </div>
                </div>
            </div>

            <!-- Standard Horizontal NavTabs Scroller -->
            <div class="bg-white rounded-2xl shadow-2xs border border-slate-200/80 p-2 relative">
                <div class="flex items-center relative">
                    <button type="button" 
                            class="btn btn-sm btn-light border border-slate-200/80 rounded-xl shadow-2xs me-1.5 hidden md:flex items-center justify-center shrink-0 text-slate-600 hover:text-blue-600 hover:bg-blue-50 transition w-[34px] h-[34px] z-5" 
                            onclick="document.getElementById('navTabsRiwayatSaya')?.scrollBy({ left: -220, behavior: 'smooth' })"
                            title="Geser ke Kiri">
                        <i class="bi bi-chevron-left"></i>
                    </button>

                    <div class="nav-tabs-wrapper grow overflow-hidden relative">
                        <ul class="flex border-0 flex-nowrap overflow-x-auto whitespace-nowrap scrollable-nav-tabs gap-1.5 px-1 select-none no-scrollbar" id="navTabsRiwayatSaya" role="tablist">
                            <li class="nav-item">
                                <button class="border-0 font-semibold px-4 py-2.5 rounded-xl text-xs transition flex items-center gap-2" 
                                        :class="activeTab === 'aktif' ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'" 
                                        @click="activeTab = 'aktif'">
                                    <i class="bi bi-hourglass-split text-sm"></i> Pinjaman Aktif Saya 
                                    <span class="px-1.5 py-0.2 rounded-full text-[10px]" :class="activeTab === 'aktif' ? 'bg-blue-500 text-white' : 'bg-slate-200 text-slate-700'">
                                        {{ props.sirkulasi_aktif?.length || 0 }}
                                    </span>
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="border-0 font-semibold px-4 py-2.5 rounded-xl text-xs transition flex items-center gap-2" 
                                        :class="activeTab === 'selesai' ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'" 
                                        @click="activeTab = 'selesai'">
                                    <i class="bi bi-clock-history text-sm"></i> Riwayat Pengembalian Terdahulu
                                </button>
                            </li>
                        </ul>
                    </div>

                    <button type="button" 
                            class="btn btn-sm btn-light border border-slate-200/80 rounded-xl shadow-2xs ms-1.5 hidden md:flex items-center justify-center shrink-0 text-slate-600 hover:text-blue-600 hover:bg-blue-50 transition w-[34px] h-[34px] z-5" 
                            onclick="document.getElementById('navTabsRiwayatSaya')?.scrollBy({ left: 220, behavior: 'smooth' })"
                            title="Geser ke Kanan">
                        <i class="bi bi-chevron-right"></i>
                    </button>
                </div>
            </div>

            <!-- Tab 1: Pinjaman Aktif -->
            <div v-if="activeTab === 'aktif'" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div v-for="item in props.sirkulasi_aktif" :key="item.id" 
                         class="bg-white p-5 rounded-2xl border shadow-xs flex flex-col justify-between"
                         :class="item.is_terlambat ? 'border-rose-300 bg-rose-50/20' : 'border-slate-200/80'">
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <span class="px-2 py-0.5 rounded font-mono text-[10px] font-bold bg-blue-50 text-blue-700">{{ item.kode_transaksi }}</span>
                                <span v-if="item.is_terlambat" class="px-2 py-0.5 rounded-full text-xs font-semibold bg-rose-100 text-rose-700">
                                    Terlambat {{ item.terlambat_hari }} Hari
                                </span>
                                <span v-else class="px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">
                                    Sisa {{ item.sisa_hari }} Hari Lagi
                                </span>
                            </div>

                            <h3 class="font-bold text-slate-800 text-sm mb-1 leading-snug">{{ item.judul_buku }}</h3>
                            <div class="text-xs text-slate-500 space-y-1 mt-3">
                                <div><i class="bi bi-upc-scan me-1 text-slate-400"></i> Barcode: <strong class="text-slate-700 font-mono">{{ item.kode_eksemplar || item.kode_buku }}</strong></div>
                                <div><i class="bi bi-calendar-event me-1 text-slate-400"></i> Dipinjam: {{ formatDate(item.tanggal_pinjam) }}</div>
                                <div class="font-semibold" :class="item.is_terlambat ? 'text-rose-600' : 'text-slate-700'">
                                    <i class="bi bi-calendar-x me-1"></i> Jatuh Tempo: {{ formatDate(item.tanggal_harus_kembali) }}
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-400">
                            <span>Perpanjangan: {{ item.perpanjangan_ke }}x</span>
                            <span class="text-slate-500 text-[11px]">Kembalikan di Meja Sirkulasi</span>
                        </div>
                    </div>

                    <div v-if="!props.sirkulasi_aktif?.length" class="col-span-2 bg-white p-12 rounded-2xl border border-dashed border-slate-300 text-center text-slate-400">
                        <i class="bi bi-bookmark-check text-4xl mb-2 text-emerald-500 block"></i>
                        Anda tidak memiliki pinjaman buku yang aktif saat ini.
                    </div>
                </div>
            </div>

            <!-- Tab 2: Riwayat Selesai -->
            <div v-if="activeTab === 'selesai'" class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-600">
                        <thead class="bg-slate-50/80 text-slate-700 font-semibold border-b border-slate-200/80 uppercase tracking-wider text-[11px]">
                            <tr>
                                <th class="px-5 py-3.5">Kode Transaksi</th>
                                <th class="px-5 py-3.5">Judul Buku</th>
                                <th class="px-5 py-3.5">Tgl Pinjam & Kembali</th>
                                <th class="px-5 py-3.5">Kondisi & Status Denda</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="item in props.sirkulasi_selesai" :key="item.id" class="hover:bg-slate-50/60">
                                <td class="px-5 py-3.5 font-mono font-semibold text-slate-700">{{ item.kode_transaksi }}</td>
                                <td class="px-5 py-3.5 font-bold text-slate-800">{{ item.judul_buku }}</td>
                                <td class="px-5 py-3.5">
                                    <div>Pinjam: {{ formatDate(item.tanggal_pinjam) }}</div>
                                    <div class="text-emerald-600 font-medium">Kembali: {{ formatDate(item.tanggal_kembali_aktual) }}</div>
                                </td>
                                <td class="px-5 py-3.5">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-semibold uppercase bg-emerald-100 text-emerald-800">
                                        {{ item.kondisi_kembali || 'Baik' }}
                                    </span>
                                    <span v-if="item.total_denda > 0" class="block text-[11px] text-rose-600 font-semibold mt-0.5">
                                        Denda: {{ formatCurrency(item.total_denda) }}
                                    </span>
                                </td>
                            </tr>
                            <tr v-if="!props.sirkulasi_selesai?.length">
                                <td colspan="4" class="px-5 py-8 text-center text-slate-400">Belum ada riwayat peminjaman terdahulu.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- TELEPORT MODAL SURAT BEBAS PUSTAKA -->
        <Teleport to="body">
            <div v-if="isModalSuratOpen" class="fixed inset-0 z-[9999] flex items-center justify-center overflow-y-auto p-4 sm:p-6">
                <div class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm transition-opacity" @click="isModalSuratOpen = false"></div>
                <div class="relative w-full max-w-xl bg-white rounded-2xl shadow-2xl border border-slate-100 overflow-hidden transform transition-all">
                    <div class="p-6 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                        <div class="flex items-center gap-2.5">
                            <div class="w-9 h-9 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center text-lg font-bold">
                                <i class="bi bi-shield-check"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-slate-800 text-base">Surat Keterangan Bebas Pustaka Mandiri</h3>
                                <p class="text-xs text-slate-500">Cetak surat bebas pustaka resmi.</p>
                            </div>
                        </div>
                        <button @click="isModalSuratOpen = false" class="text-slate-400 hover:text-slate-600 transition">
                            <i class="bi bi-x-lg text-lg"></i>
                        </button>
                    </div>

                    <div class="p-6 space-y-4">
                        <div class="border border-slate-200 rounded-xl p-6 bg-slate-50/40 text-xs space-y-3 font-sans">
                            <div class="text-center border-b border-slate-300 pb-3">
                                <div class="font-bold text-slate-800 uppercase text-sm">SURAT KETERANGAN BEBAS PERPUSTAKAAN</div>
                                <div class="text-[11px] text-slate-500">Nomor: 421.3/PERPUS/SINTA/2026</div>
                            </div>
                            <div class="space-y-1.5 text-slate-700">
                                <p>Yang bertanda tangan di bawah ini Kepala Perpustakaan menerangkan bahwa:</p>
                                <div class="grid grid-cols-3 gap-1 pt-1 font-medium">
                                    <span class="text-slate-500">Nama Pemustaka</span>
                                    <span class="col-span-2 text-slate-800">: {{ props.member_info?.nama }}</span>
                                    <span class="text-slate-500">No. Identitas / NIS</span>
                                    <span class="col-span-2 text-slate-800">: {{ props.member_info?.nomor_identitas || '-' }}</span>
                                    <span class="text-slate-500">Status / Kategori</span>
                                    <span class="col-span-2 text-slate-800 uppercase">: {{ props.member_info?.tipe }}</span>
                                </div>
                                <p class="pt-2">Dinyatakan <strong>BEBAS DARI SEGALA PINJAMAN DAN TANGGUNGAN DENDA</strong> di Perpustakaan Sekolah.</p>
                            </div>
                            <div class="pt-4 flex justify-between items-end text-[11px]">
                                <div>
                                    <div class="text-slate-400">Verifikasi Sistem: SINTA Cloud Verified</div>
                                </div>
                                <div class="text-center">
                                    <div>Kepala Perpustakaan,</div>
                                    <div class="h-10"></div>
                                    <div class="font-bold text-slate-800 underline">{{ props.pengaturan?.kepala_perpustakaan || 'Kepala Perpustakaan' }}</div>
                                    <div class="text-slate-500">NIP. {{ props.pengaturan?.nip_kepala || '-' }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="pt-2 flex items-center justify-end gap-2">
                            <button type="button" @click="isModalSuratOpen = false" class="px-4 py-2.5 rounded-xl border border-slate-200 text-slate-600 font-medium text-xs hover:bg-slate-50 transition">
                                Tutup
                            </button>
                            <button @click="cetakSurat" class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-xs shadow transition flex items-center gap-1.5">
                                <i class="bi bi-printer"></i> Cetak Dokumen
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>
