<script setup>
import { Head, router } from '@inertiajs/vue3'
import { computed } from 'vue'

const props = defineProps({
    tenant: {
        type: Object,
        default: () => ({})
    },
    invoice: {
        type: Object,
        default: null
    },
    message: {
        type: String,
        default: 'Masa aktif langganan SaaS sekolah Anda telah berakhir. Seluruh akses operasional telah dinonaktifkan sementara hingga proses perpanjangan diselesaikan.'
    }
})

const formatCurrency = (val) => {
    if (!val) return 'Rp 0'
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val)
}

const formatDate = (dateStr) => {
    if (!dateStr) return '-'
    const d = new Date(dateStr)
    return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })
}

const handleLogout = () => {
    router.post('/logout')
}

const handleToBilling = () => {
    router.visit('/sekolah/billing')
}

const contactWhatsapp = () => {
    const text = encodeURIComponent(`Halo Tim Billing SINTA SaaS, kami dari ${props.tenant?.name || 'Sekolah'} ingin melakukan konfirmasi aktivasi/perpanjangan langganan sistem SINTA.`)
    window.open(`https://wa.me/6281234567890?text=${text}`, '_blank')
}
</script>

<template>
    <Head title="Langganan Sekolah Kedaluwarsa - SINTA SaaS" />

    <div class="min-h-screen bg-slate-950 text-slate-100 flex flex-col justify-between relative overflow-hidden font-sans select-none">
        <!-- Ambient Glowing Background -->
        <div class="absolute -top-40 -left-40 w-96 h-96 bg-rose-600/20 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-40 -right-40 w-96 h-96 bg-amber-600/20 rounded-full blur-3xl pointer-events-none"></div>

        <!-- Lockout Header / Brand -->
        <header class="p-6 md:p-8 flex items-center justify-between border-b border-slate-800/80 bg-slate-900/60 backdrop-blur-md relative z-10">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-rose-600 to-amber-500 flex items-center justify-center text-white shadow-lg shadow-rose-900/40">
                    <i class="bi bi-shield-lock-fill text-xl"></i>
                </div>
                <div>
                    <h1 class="font-bold text-lg text-white tracking-wide">SINTA <span class="text-rose-400">Security Guard</span></h1>
                    <p class="text-xs text-slate-400">Subscription & Access Control Gateway</p>
                </div>
            </div>

            <button 
                @click="handleLogout" 
                class="px-4 py-2 text-xs font-semibold text-slate-300 hover:text-white bg-slate-800 hover:bg-slate-700 border border-slate-700 rounded-lg transition-all flex items-center gap-2 shadow-sm"
            >
                <i class="bi bi-box-arrow-right"></i>
                <span>Keluar dari Akun</span>
            </button>
        </header>

        <!-- Main Lockout Hero & Resolution Card -->
        <main class="flex-1 flex items-center justify-center p-4 md:p-8 relative z-10">
            <div class="max-w-2xl w-full bg-slate-900/90 border border-rose-900/40 rounded-3xl p-6 md:p-10 shadow-2xl shadow-rose-950/60 backdrop-blur-xl space-y-8 text-center">
                <!-- Icon Pulse -->
                <div class="inline-flex relative">
                    <div class="w-24 h-24 rounded-full bg-rose-500/10 border-2 border-rose-500/30 flex items-center justify-center mx-auto text-rose-400 animate-pulse">
                        <i class="bi bi-exclamation-octagon text-5xl"></i>
                    </div>
                    <span class="absolute top-0 right-0 flex h-6 w-6">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-rose-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-6 w-6 bg-rose-600 border-2 border-slate-900 text-[10px] text-white font-black items-center justify-center">!</span>
                    </span>
                </div>

                <!-- Title & Explanation -->
                <div class="space-y-3">
                    <span class="px-3 py-1 text-xs font-bold uppercase tracking-wider text-rose-300 bg-rose-950/80 border border-rose-800/60 rounded-full inline-block">
                        Status: Akun Ditangguhkan (Expired)
                    </span>
                    <h2 class="text-2xl md:text-3xl font-extrabold text-white tracking-tight">
                        Masa Langganan Sekolah Telah Berakhir
                    </h2>
                    <p class="text-sm md:text-base text-slate-300 max-w-lg mx-auto leading-relaxed">
                        Akses operasional untuk <strong class="text-rose-300 font-semibold">{{ tenant?.name || 'Sekolah Anda' }}</strong> telah dinonaktifkan sementara oleh sistem otomatis SINTA.
                    </p>
                </div>

                <!-- Bill Summary Box -->
                <div class="bg-slate-950/80 border border-slate-800 rounded-2xl p-5 text-left space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-800/80 pb-3">
                        <span class="text-xs font-semibold text-slate-400">Identitas Sekolah</span>
                        <span class="text-xs font-bold text-slate-200">{{ tenant?.name || '-' }} (NPSN: {{ tenant?.npsn || '-' }})</span>
                    </div>
                    <div class="flex items-center justify-between border-b border-slate-800/80 pb-3">
                        <span class="text-xs font-semibold text-slate-400">Paket Langganan</span>
                        <span class="text-xs font-bold text-amber-400 uppercase">{{ tenant?.subscription_plan || 'Standar SaaS' }}</span>
                    </div>
                    <div class="flex items-center justify-between border-b border-slate-800/80 pb-3">
                        <span class="text-xs font-semibold text-slate-400">Kedaluwarsa Pada</span>
                        <span class="text-xs font-bold text-rose-400">{{ formatDate(tenant?.subscription_expires_at) }}</span>
                    </div>
                    <div class="flex items-center justify-between pt-1">
                        <div>
                            <span class="text-xs font-semibold text-slate-400 block">Total Tagihan Perpanjangan</span>
                            <span class="text-xs text-slate-500">Periode {{ tenant?.billing_cycle || 'Tahunan' }}</span>
                        </div>
                        <span class="text-xl font-black text-emerald-400">
                            {{ formatCurrency(invoice?.amount || tenant?.subscription_price || 0) }}
                        </span>
                    </div>
                </div>

                <!-- Call to Action Buttons -->
                <div class="flex flex-col sm:flex-row items-center justify-center gap-3 pt-2">
                    <button 
                        @click="handleToBilling"
                        class="w-full sm:w-auto px-6 py-3.5 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-bold text-sm rounded-xl shadow-lg shadow-emerald-900/30 transition-all flex items-center justify-center gap-2"
                    >
                        <i class="bi bi-credit-card-2-front-fill text-base"></i>
                        <span>Buka Menu Billing & Bayar</span>
                    </button>

                    <button 
                        @click="contactWhatsapp"
                        class="w-full sm:w-auto px-6 py-3.5 bg-slate-800 hover:bg-slate-700 text-slate-200 hover:text-white font-semibold text-sm border border-slate-700 rounded-xl transition-all flex items-center justify-center gap-2"
                    >
                        <i class="bi bi-whatsapp text-emerald-400 text-base"></i>
                        <span>Hubungi Tim Billing SINTA</span>
                    </button>
                </div>

                <!-- Notice Note -->
                <p class="text-xs text-slate-500">
                    <i class="bi bi-info-circle mr-1"></i>
                    Setelah pembayaran berhasil diverifikasi atau disetujui, seluruh modul akademik, kesiswaan, dan keuangan akan aktif kembali secara instan tanpa kehilangan data.
                </p>
            </div>
        </main>

        <!-- Lockout Footer -->
        <footer class="p-6 text-center text-xs text-slate-500 border-t border-slate-900/80 relative z-10">
            &copy; 2026 SINTA SaaS Platform Engine. Dilindungi standar keamanan OWASP ASVS L3 & Isolasi Multi-Tenant.
        </footer>
    </div>
</template>
