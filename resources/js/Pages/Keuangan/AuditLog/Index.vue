<script setup>
import { ref, reactive, computed, watch } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import SearchableSelect from '@/Components/SearchableSelect.vue'

const props = defineProps({
    logs: Object,
    metrics: Object,
    isSuperAdmin: Boolean,
    tenantsList: Array,
    filters: Object,
})

// State Filter
const filterForm = reactive({
    tenant_id: props.filters?.tenant_id || '',
    event_type: props.filters?.event_type || '',
    date_from: props.filters?.date_from || '',
    date_to: props.filters?.date_to || '',
    search: props.filters?.search || '',
})

const isSearching = ref(false)

const tenantOptions = computed(() => [
    { value: '', label: '-- Semua Sekolah (Agregat Global) --', sublabel: 'Tampilkan audit log seluruh tenant' },
    ...props.tenantsList.map(t => ({
        value: t.id,
        label: t.nama_sekolah,
        sublabel: 'Tenant ID: ' + t.id.substring(0, 8) + '...'
    }))
])

const eventTypeOptions = [
    { value: '', label: 'Semua Kategori Aktivitas' },
    { value: 'PAYMENT_SPP', label: 'PAYMENT_SPP - Penerimaan Kas Kasir', sublabel: 'Pembayaran tagihan siswa' },
    { value: 'VOID_PAYMENT', label: 'VOID_PAYMENT - Pembatalan Transaksi', sublabel: 'Rollback pembayaran & saldo' },
    { value: 'GENERATE_TAGIHAN', label: 'GENERATE_TAGIHAN - Pembuatan Tagihan', sublabel: 'Batch invoicing masal' },
    { value: 'UPDATE_POS', label: 'UPDATE_POS - Modifikasi Tarif / Pos', sublabel: 'Master tarif & pos' },
    { value: 'DELETE_TAGIHAN', label: 'DELETE_TAGIHAN - Hapus Tagihan', sublabel: 'Penghapusan invoice' },
    { value: 'AUTH_LOGIN', label: 'AUTH_LOGIN - Akses Masuk Kasir', sublabel: 'Aktivitas otentikasi' },
]

const applyFilters = () => {
    isSearching.value = true
    router.get('/keuangan/audit-log', {
        tenant_id: filterForm.tenant_id || undefined,
        event_type: filterForm.event_type || undefined,
        date_from: filterForm.date_from || undefined,
        date_to: filterForm.date_to || undefined,
        search: filterForm.search || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
        onFinish: () => {
            isSearching.value = false
        }
    })
}

const resetFilters = () => {
    filterForm.tenant_id = ''
    filterForm.event_type = ''
    filterForm.date_from = ''
    filterForm.date_to = ''
    filterForm.search = ''
    applyFilters()
}

// Modal Detail Snapshot Log
const showDetailModal = ref(false)
const selectedLog = ref(null)

const openDetailModal = (log) => {
    selectedLog.value = log
    showDetailModal.value = true
}

const closeDetailModal = () => {
    showDetailModal.value = false
    selectedLog.value = null
}

// Format Helper
const formatRupiah = (val) => {
    if (!val || isNaN(val)) return 'Rp 0'
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val)
}

const formatDateTime = (dateStr) => {
    if (!dateStr) return '-'
    const d = new Date(dateStr)
    return d.toLocaleString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    })
}

const getEventBadgeClass = (eventType) => {
    switch (eventType) {
        case 'PAYMENT_SPP':
            return 'bg-emerald-50 text-emerald-700 border-emerald-200'
        case 'VOID_PAYMENT':
            return 'bg-rose-50 text-rose-700 border-rose-200'
        case 'GENERATE_TAGIHAN':
            return 'bg-blue-50 text-blue-700 border-blue-200'
        case 'UPDATE_POS':
        case 'DELETE_TAGIHAN':
            return 'bg-amber-50 text-amber-700 border-amber-200'
        default:
            return 'bg-slate-50 text-slate-700 border-slate-200'
    }
}
</script>

<template>
    <Head title="Audit Trail & Rekam Jejak Keuangan" />

    <AppLayout title="Audit Trail & Rekam Jejak Keuangan">
        <div class="space-y-6 pb-12">
            <!-- Header Halaman -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-purple-50 text-purple-600 border border-purple-100 font-bold">
                            <i class="bi bi-shield-check text-xl"></i>
                        </span>
                        <div>
                            <h1 class="text-2xl font-bold text-slate-800">Audit Trail Keuangan</h1>
                            <p class="text-xs text-slate-500">Log keamanan transaksi mutlak, rekam jejak kasir, void pembatalan, dan modifikasi data</p>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <button 
                        @click="resetFilters" 
                        class="px-4 py-2 bg-slate-100 text-slate-700 text-xs font-semibold rounded-xl hover:bg-slate-200 transition flex items-center gap-2">
                        <i class="bi bi-arrow-clockwise"></i> Reset Filter
                    </button>
                    <Link 
                        href="/keuangan/dashboard" 
                        class="px-4 py-2 bg-blue-600 text-white text-xs font-semibold rounded-xl hover:bg-blue-700 transition flex items-center gap-2 shadow-xs">
                        <i class="bi bi-speedometer2"></i> Dashboard Keuangan
                    </Link>
                </div>
            </div>

            <!-- Metric Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
                    <div>
                        <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Log Sistem</div>
                        <div class="text-2xl font-black text-slate-800 mt-1">{{ metrics?.total_log ?? 0 }}</div>
                        <div class="text-[11px] text-slate-400 mt-0.5">Semua entri audit trail</div>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 border border-blue-100 flex items-center justify-center text-xl">
                        <i class="bi bi-journal-text"></i>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
                    <div>
                        <div class="text-xs font-semibold text-emerald-600 uppercase tracking-wider">Transaksi Kasir</div>
                        <div class="text-2xl font-black text-emerald-700 mt-1">{{ metrics?.total_pembayaran ?? 0 }}</div>
                        <div class="text-[11px] text-emerald-500 mt-0.5">Pembayaran SPP & tagihan</div>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center text-xl">
                        <i class="bi bi-cash-stack"></i>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
                    <div>
                        <div class="text-xs font-semibold text-rose-600 uppercase tracking-wider">Void Pembatalan</div>
                        <div class="text-2xl font-black text-rose-700 mt-1">{{ metrics?.total_void ?? 0 }}</div>
                        <div class="text-[11px] text-rose-500 mt-0.5">Transaksi di-rollback</div>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-rose-50 text-rose-600 border border-rose-100 flex items-center justify-center text-xl">
                        <i class="bi bi-x-circle"></i>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
                    <div>
                        <div class="text-xs font-semibold text-amber-600 uppercase tracking-wider">Modifikasi Sistem</div>
                        <div class="text-2xl font-black text-amber-700 mt-1">{{ metrics?.total_modifikasi ?? 0 }}</div>
                        <div class="text-[11px] text-amber-500 mt-0.5">Generate & update pos</div>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 border border-amber-100 flex items-center justify-center text-xl">
                        <i class="bi bi-sliders"></i>
                    </div>
                </div>
            </div>

            <!-- Filter Card -->
            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                    <div class="md:col-span-4">
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Tenant / Asal Sekolah</label>
                        <SearchableSelect 
                            v-model="filterForm.tenant_id" 
                            :options="tenantOptions"
                            placeholder="Pilih atau cari sekolah..."
                            searchPlaceholder="Ketik nama sekolah..."
                        />
                    </div>

                    <div class="md:col-span-3">
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Kategori Aktivitas</label>
                        <SearchableSelect 
                            v-model="filterForm.event_type" 
                            :options="eventTypeOptions"
                            placeholder="Pilih tipe event..."
                            searchPlaceholder="Ketik nama event..."
                        />
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Dari Tanggal</label>
                        <input 
                            type="date" 
                            v-model="filterForm.date_from" 
                            class="w-full px-3 py-2 text-xs border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500"
                        />
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Sampai Tanggal</label>
                        <input 
                            type="date" 
                            v-model="filterForm.date_to" 
                            class="w-full px-3 py-2 text-xs border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500"
                        />
                    </div>

                    <div class="md:col-span-1">
                        <button 
                            @click="applyFilters" 
                            :disabled="isSearching"
                            class="w-full py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-semibold transition flex items-center justify-center gap-1 shadow-xs disabled:opacity-50">
                            <i class="bi bi-funnel"></i> Filter
                        </button>
                    </div>
                </div>

                <div class="pt-2 border-t border-slate-100 flex items-center justify-between">
                    <div class="relative w-full max-w-md">
                        <input 
                            type="text" 
                            v-model="filterForm.search" 
                            @keyup.enter="applyFilters"
                            placeholder="Cari keterangan, kata kunci IP, user..." 
                            class="w-full pl-9 pr-3 py-2 text-xs border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500"
                        />
                        <i class="bi bi-search absolute left-3 top-2.5 text-slate-400 text-xs"></i>
                    </div>

                    <button 
                        @click="resetFilters" 
                        class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-lg text-xs font-semibold transition flex items-center gap-1.5">
                        <i class="bi bi-arrow-clockwise"></i> Reset
                    </button>
                </div>
            </div>

            <!-- Tabel Log Audit -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                <div class="p-4 border-b border-slate-100 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <i class="bi bi-list-columns-reverse text-blue-600"></i>
                        <span class="text-xs font-bold text-slate-700">Daftar Rekam Jejak Aktivitas Keuangan</span>
                    </div>
                    <div class="text-xs text-slate-500 font-medium">
                        Menampilkan {{ logs?.data?.length || 0 }} dari {{ logs?.total || 0 }} entri
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200/80 text-slate-600 font-semibold">
                                <th class="p-3 w-12 text-center">No</th>
                                <th class="p-3 w-40">Waktu (WIB)</th>
                                <th class="p-3 w-48">Pengguna & Peran</th>
                                <th class="p-3 w-40">Tipe Aktivitas</th>
                                <th class="p-3">Keterangan Aktivitas</th>
                                <th class="p-3 w-32 text-right">Nominal</th>
                                <th class="p-3 w-32">IP & Browser</th>
                                <th class="p-3 w-20 text-center">Snapshot</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-if="!logs?.data || logs?.data?.length === 0">
                                <td colspan="8" class="p-12 text-center text-slate-400">
                                    <i class="bi bi-inbox text-4xl block mb-2"></i>
                                    Tidak ada catatan log audit yang sesuai dengan kriteria filter.
                                </td>
                            </tr>
                            <tr v-for="(log, idx) in logs?.data" :key="log.id" class="hover:bg-slate-50/80 transition">
                                <td class="p-3 text-center text-slate-400 font-medium">
                                    {{ (logs?.current_page - 1) * logs?.per_page + idx + 1 }}
                                </td>
                                <td class="p-3 whitespace-nowrap text-slate-600 font-mono text-[11px]">
                                    <i class="bi bi-clock me-1 text-slate-400"></i>
                                    {{ formatDateTime(log.created_at) }}
                                </td>
                                <td class="p-3">
                                    <div class="font-semibold text-slate-800">{{ log.user?.nama_lengkap || log.user?.username || 'Sistem Otomatis' }}</div>
                                    <span class="inline-block px-2 py-0.5 text-[10px] font-semibold rounded-md bg-slate-100 text-slate-600 border border-slate-200 mt-0.5">
                                        {{ log.user_role || 'system' }}
                                    </span>
                                </td>
                                <td class="p-3 whitespace-nowrap">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-[11px] font-bold rounded-lg border" :class="getEventBadgeClass(log.event_type)">
                                        <i class="bi bi-circle-fill text-[6px]"></i>
                                        {{ log.event_type }}
                                    </span>
                                </td>
                                <td class="p-3 text-slate-700">
                                    <div class="line-clamp-2 leading-relaxed">{{ log.keterangan }}</div>
                                </td>
                                <td class="p-3 text-right font-mono font-bold" :class="log.nominal > 0 ? (log.event_type === 'VOID_PAYMENT' ? 'text-rose-600' : 'text-emerald-600') : 'text-slate-400'">
                                    {{ log.nominal > 0 ? formatRupiah(log.nominal) : '-' }}
                                </td>
                                <td class="p-3 text-[11px] text-slate-500 font-mono">
                                    <div>{{ log.ip_address || '127.0.0.1' }}</div>
                                    <div class="text-[10px] text-slate-400 truncate max-w-[120px]" :title="log.user_agent">{{ log.user_agent || 'Mozilla/5.0' }}</div>
                                </td>
                                <td class="p-3 text-center">
                                    <button 
                                        @click="openDetailModal(log)" 
                                        class="px-2.5 py-1.5 bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white rounded-lg transition text-xs font-semibold flex items-center justify-center gap-1 border border-blue-100 shadow-2xs"
                                        title="Lihat Snapshot JSON">
                                        <i class="bi bi-code-square"></i> Data
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Paginasi -->
                <div v-if="logs?.links && logs?.links?.length > 3" class="p-4 border-t border-slate-100 flex items-center justify-between">
                    <div class="text-xs text-slate-500">
                        Halaman {{ logs.current_page }} dari {{ logs.last_page }}
                    </div>
                    <div class="flex items-center gap-1">
                        <component 
                            v-for="(link, lIdx) in logs.links" 
                            :key="lIdx"
                            :is="link.url ? 'a' : 'span'"
                            :href="link.url"
                            v-html="link.label"
                            class="px-3 py-1.5 text-xs rounded-lg border font-medium transition"
                            :class="link.active ? 'bg-blue-600 text-white border-blue-600' : (link.url ? 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50' : 'bg-slate-50 text-slate-300 border-slate-100 cursor-not-allowed')"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Teleport Modal Detail Snapshot JSON -->
        <Teleport to="body">
            <div v-if="showDetailModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4 animate-fade-in">
                <div class="bg-white w-full max-w-2xl rounded-2xl shadow-2xl border border-slate-200 overflow-hidden flex flex-col max-h-[90vh]">
                    <!-- Modal Header -->
                    <div class="px-6 py-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="p-2 rounded-lg bg-blue-50 text-blue-600 border border-blue-100">
                                <i class="bi bi-code-slash text-base"></i>
                            </span>
                            <div>
                                <h3 class="text-sm font-bold text-slate-800">Snapshot Audit Trail Keuangan</h3>
                                <p class="text-[11px] text-slate-500">Rekam snapshot perubahan data database</p>
                            </div>
                        </div>
                        <button @click="closeDetailModal" class="text-slate-400 hover:text-slate-600 text-lg transition">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>

                    <!-- Modal Body -->
                    <div class="p-6 overflow-y-auto space-y-4 text-xs">
                        <div class="grid grid-cols-2 gap-4 bg-slate-50 p-3 rounded-xl border border-slate-200/80">
                            <div>
                                <span class="text-slate-400 block text-[10px]">TIPE EVENT:</span>
                                <span class="font-bold text-slate-800">{{ selectedLog?.event_type }}</span>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[10px]">WAKTU EKSEKUSI:</span>
                                <span class="font-mono text-slate-800">{{ formatDateTime(selectedLog?.created_at) }}</span>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[10px]">EKSEKUTOR:</span>
                                <span class="font-semibold text-slate-800">{{ selectedLog?.user?.nama_lengkap || 'Sistem' }} ({{ selectedLog?.user_role }})</span>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[10px]">NOMINAL TRANSAKSI:</span>
                                <span class="font-mono font-bold text-emerald-600">{{ formatRupiah(selectedLog?.nominal) }}</span>
                            </div>
                        </div>

                        <div>
                            <span class="text-xs font-bold text-slate-700 block mb-1">Keterangan Aksi:</span>
                            <div class="p-3 bg-slate-100/80 rounded-xl text-slate-700 leading-relaxed font-sans">
                                {{ selectedLog?.keterangan }}
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <span class="text-xs font-bold text-amber-700 flex items-center gap-1 mb-1">
                                    <i class="bi bi-arrow-left-circle"></i> Snapshot Data Sebelumnya (Old)
                                </span>
                                <pre class="p-3 bg-slate-900 text-amber-400 rounded-xl font-mono text-[11px] overflow-x-auto max-h-56 leading-tight">{{ JSON.stringify(selectedLog?.old_data, null, 2) || 'null' }}</pre>
                            </div>
                            <div>
                                <span class="text-xs font-bold text-emerald-700 flex items-center gap-1 mb-1">
                                    <i class="bi bi-arrow-right-circle"></i> Snapshot Data Baru (New)
                                </span>
                                <pre class="p-3 bg-slate-900 text-emerald-400 rounded-xl font-mono text-[11px] overflow-x-auto max-h-56 leading-tight">{{ JSON.stringify(selectedLog?.new_data, null, 2) || 'null' }}</pre>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="px-6 py-3 bg-slate-50 border-t border-slate-200 flex justify-end">
                        <button @click="closeDetailModal" class="px-4 py-2 bg-slate-200 text-slate-700 rounded-xl text-xs font-semibold hover:bg-slate-300 transition">
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>
