<?php
/**
 * View: Dashboard E-Arsip & Persuratan Tata Usaha
 * SINTA SaaS Platform — Modern Vue 3 Architecture & Dynamic PostgreSQL Multi-Schema
 */
$activeMenu = 'dashboard';
$pageTitle = 'Dashboard Persuratan & Tata Usaha';
$pageSubtitle = 'Ringkasan statistik volume arsip naskah dinas, status disposisi pimpinan, dan antrean pengajuan pemanggilan siswa.';
$pageIcon = 'bi-speedometer2';
?>
<style>
.card-action-tile {
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}
.card-action-tile:hover {
    transform: translateX(4px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}
</style>

<div id="persuratanDashboardApp" v-cloak class="container-fluid px-0">
    <!-- Hero Banner Header Mandiri -->
    <?php 
    $heroBadge = 'Pusat Eksekutif Persuratan';
    include __DIR__ . '/_hero_header.php'; 
    ?>

    <!-- Loading State -->
    <div v-if="loading" class="card border-0 shadow-2xs rounded-2xl bg-white p-5 text-center my-3">
        <div class="spinner-border spinner-border-sm text-primary me-2"></div>
        <span class="text-slate-500 text-xs font-semibold">Memuat statistik persuratan...</span>
    </div>

    <div v-else>
        <!-- ═══════════════════════════════════════════════════════════════════════
             1. 4 STAT METRIC CARDS (DESAIN MODERN & INTERAKTIF)
             ═══════════════════════════════════════════════════════════════════════ -->
        <div class="row g-3 g-md-3.5 mb-4">
            <!-- Card 1: Surat Masuk -->
            <div class="col-6 col-lg-3">
                <div class="card border-0 shadow-xs rounded-2xl bg-white p-3.5 p-md-4 h-100 position-relative overflow-hidden transition-all duration-200 hover:-translate-y-1 hover:shadow-md" style="border: 1px solid #e2e8f0;">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <span class="text-slate-500 text-xs font-bold uppercase tracking-wider">Surat Masuk</span>
                        <div class="w-10 h-10 rounded-xl d-flex align-items-center justify-content-center shadow-xs" style="background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); color: #2563eb;">
                            <i class="bi bi-inbox-fill fs-5"></i>
                        </div>
                    </div>
                    <div class="h3 font-black text-slate-800 mb-1 tracking-tight">{{ stats.total_surat_masuk || 0 }}</div>
                    <div class="d-flex align-items-center gap-1.5 text-slate-400 text-xs">
                        <i class="bi bi-journal-text text-blue-500"></i>
                        <span>Buku agenda surat masuk</span>
                    </div>
                </div>
            </div>

            <!-- Card 2: Surat Keluar -->
            <div class="col-6 col-lg-3">
                <div class="card border-0 shadow-xs rounded-2xl bg-white p-3.5 p-md-4 h-100 position-relative overflow-hidden transition-all duration-200 hover:-translate-y-1 hover:shadow-md" style="border: 1px solid #e2e8f0;">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <span class="text-slate-500 text-xs font-bold uppercase tracking-wider">Surat Keluar</span>
                        <div class="w-10 h-10 rounded-xl d-flex align-items-center justify-content-center shadow-xs" style="background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%); color: #059669;">
                            <i class="bi bi-send-fill fs-5"></i>
                        </div>
                    </div>
                    <div class="h3 font-black text-emerald-600 mb-1 tracking-tight">{{ stats.total_surat_keluar || 0 }}</div>
                    <div class="d-flex align-items-center gap-1.5 text-slate-400 text-xs">
                        <i class="bi bi-check-circle-fill text-emerald-500"></i>
                        <span>Register surat keluar terbit</span>
                    </div>
                </div>
            </div>

            <!-- Card 3: Menunggu Disposisi -->
            <div class="col-6 col-lg-3">
                <div class="card border-0 shadow-xs rounded-2xl bg-white p-3.5 p-md-4 h-100 position-relative overflow-hidden transition-all duration-200 hover:-translate-y-1 hover:shadow-md" style="border: 1px solid #e2e8f0;">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <span class="text-slate-500 text-xs font-bold uppercase tracking-wider">Menunggu Disposisi</span>
                        <div class="w-10 h-10 rounded-xl d-flex align-items-center justify-content-center shadow-xs" style="background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%); color: #d97706;">
                            <i class="bi bi-hourglass-split fs-5"></i>
                        </div>
                    </div>
                    <div class="h3 font-black text-amber-600 mb-1 tracking-tight">{{ stats.disposisi_pending || 0 }}</div>
                    <div class="d-flex align-items-center gap-1.5 text-slate-400 text-xs">
                        <i class="bi bi-clock-history text-amber-500"></i>
                        <span>Surat masuk belum didisposisi</span>
                    </div>
                </div>
            </div>

            <!-- Card 4: Pengajuan BK Pending -->
            <div class="col-6 col-lg-3">
                <div class="card border-0 shadow-xs rounded-2xl bg-white p-3.5 p-md-4 h-100 position-relative overflow-hidden transition-all duration-200 hover:-translate-y-1 hover:shadow-md" style="border: 1px solid #fed7aa; background: linear-gradient(to bottom, #ffffff, #fff7ed);">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <span class="text-slate-500 text-xs font-bold uppercase tracking-wider">Pengajuan BK</span>
                        <div class="w-10 h-10 rounded-xl d-flex align-items-center justify-content-center shadow-xs" style="background: linear-gradient(135deg, #fff1f2 0%, #ffe4e6 100%); color: #e11d48;">
                            <i class="bi bi-bell-fill fs-5"></i>
                        </div>
                    </div>
                    <div class="h3 font-black text-rose-600 mb-1 tracking-tight">{{ stats.pengajuan_bk_pending || 0 }}</div>
                    <div class="d-flex align-items-center gap-1.5 text-slate-400 text-xs">
                        <i class="bi bi-exclamation-circle-fill text-rose-500"></i>
                        <span>Permintaan terbit surat panggilan</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════════════════════════════════
             2. REKAPITULASI VOLUME ARSIP & AKSI CEPAT TATA USAHA
             ═══════════════════════════════════════════════════════════════════════ -->
        <div class="row g-3 g-md-4 mb-4">
            <!-- Left: Chart Data Table -->
            <div class="col-12 col-lg-7 col-xl-8">
                <div class="card border-0 shadow-xs rounded-2xl bg-white p-4 h-100" style="border: 1px solid #e2e8f0;">
                    <div class="d-flex align-items-center justify-content-between mb-3 pb-3 border-bottom">
                        <div class="d-flex align-items-center gap-2.5">
                            <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 d-flex align-items-center justify-content-center">
                                <i class="bi bi-bar-chart-line-fill fs-6"></i>
                            </div>
                            <div>
                                <h6 class="font-bold text-slate-800 fs-6 mb-0">Rekapitulasi Volume Arsip Naskah Dinas</h6>
                                <small class="text-slate-400 text-xs">Statistik pergerakan surat 6 bulan terakhir</small>
                            </div>
                        </div>
                        <span class="badge px-3 py-1.5 rounded-pill text-xs font-bold" style="background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0;">
                            <i class="bi bi-shield-check text-emerald-600 me-1"></i> Data Real-Time
                        </span>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle text-xs mb-0">
                            <thead class="text-slate-500 font-bold" style="background: #f8fafc; border-radius: 10px;">
                                <tr>
                                    <th class="py-3 ps-3 rounded-start-3">Periode Bulan</th>
                                    <th class="py-3 text-center" style="width: 130px;">Surat Masuk</th>
                                    <th class="py-3 text-center" style="width: 130px;">Surat Keluar</th>
                                    <th class="py-3 pe-3 rounded-end-3" style="width: 220px;">Visualisasi Rasio</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-if="!stats.chart_data || stats.chart_data.length === 0">
                                    <td colspan="4" class="text-center py-5 text-slate-400">
                                        <i class="bi bi-inbox fs-2 d-block mb-1 text-slate-300"></i>
                                        Belum ada riwayat aktivitas arsip surat pada periode ini.
                                    </td>
                                </tr>
                                <tr v-for="c in stats.chart_data" :key="c.bulan">
                                    <td class="ps-3 font-bold text-slate-800">
                                        <i class="bi bi-calendar-event me-1.5 text-slate-400"></i> {{ c.bulan }}
                                    </td>
                                    <td class="text-center">
                                        <span class="badge rounded-pill font-bold px-2.5 py-1 text-xs" style="background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe;">
                                            <i class="bi bi-arrow-down-left text-blue-600 me-1"></i> {{ c.surat_masuk }} Surat
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge rounded-pill font-bold px-2.5 py-1 text-xs" style="background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0;">
                                            <i class="bi bi-arrow-up-right text-emerald-600 me-1"></i> {{ c.surat_keluar }} Surat
                                        </span>
                                    </td>
                                    <td class="pe-3">
                                        <div class="progress rounded-pill bg-slate-100 overflow-hidden shadow-inner" style="height: 9px;">
                                            <div class="progress-bar" style="background: linear-gradient(90deg, #3b82f6, #2563eb);" :style="{ width: Math.min(100, (c.surat_masuk * 15)) + '%' }" :title="'Surat Masuk: ' + c.surat_masuk"></div>
                                            <div class="progress-bar" style="background: linear-gradient(90deg, #10b981, #059669);" :style="{ width: Math.min(100, (c.surat_keluar * 15)) + '%' }" :title="'Surat Keluar: ' + c.surat_keluar"></div>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Right: Action Cards (Modern Interactive Tiles) -->
            <div class="col-12 col-lg-5 col-xl-4">
                <div class="card border-0 shadow-xs rounded-2xl bg-white p-4 h-100" style="border: 1px solid #e2e8f0;">
                    <div class="d-flex align-items-center gap-2 mb-3 pb-3 border-bottom">
                        <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 d-flex align-items-center justify-content-center">
                            <i class="bi bi-lightning-charge-fill fs-6"></i>
                        </div>
                        <div>
                            <h6 class="font-bold text-slate-800 fs-6 mb-0">Aksi Cepat Tata Usaha</h6>
                            <small class="text-slate-400 text-xs">Pintasan navigasi naskah dinas &amp; arsip</small>
                        </div>
                    </div>

                    <div class="d-flex flex-column gap-2.5">
                        <!-- Action 1: Antrean BK -->
                        <a href="<?= $this->getBaseUrl() ?>/persuratan/pengajuan-bk" 
                           class="card-action-tile d-flex align-items-center justify-content-between p-3 rounded-2xl text-decoration-none transition-all duration-200"
                           style="background: #fff5f5; border: 1px solid #fed7d7;">
                            <div class="d-flex align-items-center gap-3">
                                <div class="w-10 h-10 rounded-xl d-flex align-items-center justify-content-center bg-white text-rose-600 shadow-2xs">
                                    <i class="bi bi-bell-fill fs-5"></i>
                                </div>
                                <div>
                                    <div class="font-bold text-rose-900 text-xs mb-0.5">Antrean Surat Panggilan BK</div>
                                    <div class="text-rose-600 text-[11px]">Permohonan surat dari Guru BK</div>
                                </div>
                            </div>
                            <span class="badge bg-rose-600 text-white rounded-pill px-2.5 py-1 text-xs font-black shadow-2xs">
                                {{ stats.pengajuan_bk_pending || 0 }}
                            </span>
                        </a>

                        <!-- Action 2: Surat Masuk -->
                        <a href="<?= $this->getBaseUrl() ?>/persuratan/surat-masuk" 
                           class="card-action-tile d-flex align-items-center justify-content-between p-3 rounded-2xl text-decoration-none transition-all duration-200"
                           style="background: #f8fafc; border: 1px solid #e2e8f0;">
                            <div class="d-flex align-items-center gap-3">
                                <div class="w-10 h-10 rounded-xl d-flex align-items-center justify-content-center bg-blue-50 text-blue-600 shadow-2xs">
                                    <i class="bi bi-inbox-fill fs-5"></i>
                                </div>
                                <div>
                                    <div class="font-bold text-slate-800 text-xs mb-0.5">Agenda &amp; Catat Surat Masuk</div>
                                    <div class="text-slate-500 text-[11px]">Registrasi buku agenda &amp; arsip file</div>
                                </div>
                            </div>
                            <i class="bi bi-chevron-right text-slate-400 fs-7"></i>
                        </a>

                        <!-- Action 3: Surat Keluar -->
                        <a href="<?= $this->getBaseUrl() ?>/persuratan/surat-keluar" 
                           class="card-action-tile d-flex align-items-center justify-content-between p-3 rounded-2xl text-decoration-none transition-all duration-200"
                           style="background: #f8fafc; border: 1px solid #e2e8f0;">
                            <div class="d-flex align-items-center gap-3">
                                <div class="w-10 h-10 rounded-xl d-flex align-items-center justify-content-center bg-emerald-50 text-emerald-600 shadow-2xs">
                                    <i class="bi bi-send-plus-fill fs-5"></i>
                                </div>
                                <div>
                                    <div class="font-bold text-slate-800 text-xs mb-0.5">Buat &amp; Register Surat Keluar</div>
                                    <div class="text-slate-500 text-[11px]">Nomor otomatis &amp; TTE QR Code</div>
                                </div>
                            </div>
                            <i class="bi bi-chevron-right text-slate-400 fs-7"></i>
                        </a>

                        <!-- Action 4: Template Naskah -->
                        <a href="<?= $this->getBaseUrl() ?>/persuratan/template" 
                           class="card-action-tile d-flex align-items-center justify-content-between p-3 rounded-2xl text-decoration-none transition-all duration-200"
                           style="background: #f8fafc; border: 1px solid #e2e8f0;">
                            <div class="d-flex align-items-center gap-3">
                                <div class="w-10 h-10 rounded-xl d-flex align-items-center justify-content-center bg-indigo-50 text-indigo-600 shadow-2xs">
                                    <i class="bi bi-file-earmark-richtext-fill fs-5"></i>
                                </div>
                                <div>
                                    <div class="font-bold text-slate-800 text-xs mb-0.5">Kelola Template Naskah Dinas</div>
                                    <div class="text-slate-500 text-[11px]">Format surat &amp; variabel otomatis</div>
                                </div>
                            </div>
                            <i class="bi bi-chevron-right text-slate-400 fs-7"></i>
                        </a>

                        <!-- Action 5: Master Kop & Klasifikasi -->
                        <a href="<?= $this->getBaseUrl() ?>/persuratan/master" 
                           class="card-action-tile d-flex align-items-center justify-content-between p-3 rounded-2xl text-decoration-none transition-all duration-200"
                           style="background: #f8fafc; border: 1px solid #e2e8f0;">
                            <div class="d-flex align-items-center gap-3">
                                <div class="w-10 h-10 rounded-xl d-flex align-items-center justify-content-center bg-slate-100 text-slate-600 shadow-2xs">
                                    <i class="bi bi-gear-wide-connected fs-5"></i>
                                </div>
                                <div>
                                    <div class="font-bold text-slate-800 text-xs mb-0.5">Pengaturan Kop &amp; Klasifikasi</div>
                                    <div class="text-slate-500 text-[11px]">Header kop naskah &amp; kode arsip</div>
                                </div>
                            </div>
                            <i class="bi bi-chevron-right text-slate-400 fs-7"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
if (typeof Vue !== 'undefined') {
    const { ref, onMounted } = Vue;

    const persuratanDashboardAppConfig = {
        setup() {
            const loading = ref(false);
            const stats = ref({
                total_surat_masuk: 0,
                total_surat_keluar: 0,
                disposisi_pending: 0,
                pengajuan_bk_pending: 0,
                chart_data: []
            });

            const urlParams = new URLSearchParams(window.location.search);
            const currentTenantId = urlParams.get('tenant_id') || '<?= htmlspecialchars($selectedTenantId ?? '', ENT_QUOTES, 'UTF-8') ?>';
            const getTenantParam = (prefix = '?') => {
                return currentTenantId ? `${prefix}tenant_id=${encodeURIComponent(currentTenantId)}` : '';
            };

            const fetchStats = async () => {
                loading.value = true;
                try {
                    const res = await axios.get('<?= $this->getBaseUrl() ?>/api/v1/persuratan/dashboard/stats' + getTenantParam('?'));
                    if (res.data && res.data.success) {
                        stats.value = res.data.data || stats.value;
                    }
                } catch (e) {
                    console.error('Gagal memuat statistik persuratan:', e);
                } finally {
                    loading.value = false;
                }
            };

            onMounted(() => {
                fetchStats();
            });

            return {
                loading,
                stats,
                fetchStats
            };
        }
    };

    if (window.VueAppRegistry && typeof window.VueAppRegistry.register === 'function') {
        window.VueAppRegistry.register('#persuratanDashboardApp', persuratanDashboardAppConfig);
        if (typeof window.VueAppRegistry.mountAll === 'function') {
            window.VueAppRegistry.mountAll();
        }
    } else {
        document.addEventListener('DOMContentLoaded', () => {
            Vue.createApp(persuratanDashboardAppConfig).mount('#persuratanDashboardApp');
        });
    }
}
</script>
