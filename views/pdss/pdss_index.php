<?php
/**
 * View: PDSS & Alumni Career Tracking Module Dashboard
 * Stack: Vue 3 + Tailwind CSS (preflight OFF to avoid conflict with Bootstrap)
 */
$userRole   = $data['user_role']   ?? $userRole ?? $user_role ?? ($_SESSION['role_name']    ?? '');
$tenantList = $data['tenant_list'] ?? $tenantList ?? $tenant_list ?? [];
$tenantId   = $data['tenant_id']   ?? $tenantId ?? $tenant_id ?? ($_GET['tenant_id'] ?? (App\Core\SessionManager::getTenantId() ?: ''));
if ($userRole === 'super_admin' && empty($tenantId) && !empty($tenantList)) {
    $tenantId = $tenantList[0]['id'];
}
$canWrite   = $data['can_write']   ?? $can_write ?? in_array($userRole, ['super_admin', 'guru_bk', 'operator_sekolah']);
?>


<style>
/* Custom styling to keep SINTA visual aesthetics premium */
.badge-eligible {
    background-color: #d1fae5;
    color: #065f46;
    border: 1px solid #a7f3d0;
}
.badge-not-eligible {
    background-color: #fee2e2;
    color: #991b1b;
    border: 1px solid #fecaca;
}
.progress-bar-anim {
    transition: width 0.6s cubic-bezier(0.4, 0, 0.2, 1);
}
.tab-active {
    color: #2563eb !important;
    border-bottom: 2px solid #2563eb !important;
}
[v-cloak] { display: none !important; }
    /* Navigation Tabs Styling */
    .scrollable-nav-tabs {
        padding-bottom: 5px;
        border-bottom: none;
    }
    .scrollable-nav-tabs::-webkit-scrollbar {
        height: 4px;
    }
    .scrollable-nav-tabs::-webkit-scrollbar-thumb {
        background-color: #cbd5e1;
        border-radius: 4px;
    }
    .nav-tabs-wrapper .nav-link {
        font-size: 14px;
        color: #475569;
        background-color: transparent;
        border: none;
        border-bottom: 2px solid transparent;
        border-radius: 0;
        font-weight: 600;
        padding: 10px 16px;
        transition: all 0.2s ease-in-out;
    }
    .nav-tabs-wrapper .nav-link:hover {
        color: #2563eb;
    }
    .nav-tabs-wrapper .nav-link.active {
        color: #2563eb !important;
        background-color: transparent !important;
        border-bottom: 2px solid #2563eb !important;
    }
    /* Navigation Tabs Styling */
    .scrollable-nav-tabs {
        padding-bottom: 5px;
        border-bottom: none;
    }
    .scrollable-nav-tabs::-webkit-scrollbar {
        height: 4px;
    }
    .scrollable-nav-tabs::-webkit-scrollbar-thumb {
        background-color: #cbd5e1;
        border-radius: 4px;
    }
    .nav-tabs-wrapper .nav-link {
        font-size: 14px;
        color: #475569;
        background-color: transparent;
        border: none;
        border-bottom: 2px solid transparent;
        border-radius: 0;
        font-weight: 600;
        padding: 10px 16px;
        transition: all 0.2s ease-in-out;
    }
    .nav-tabs-wrapper .nav-link:hover {
        color: #2563eb;
    }
    .nav-tabs-wrapper .nav-link.active {
        color: #2563eb !important;
        background-color: transparent !important;
        border-bottom: 2px solid #2563eb !important;
    }
</style>

<!-- Super Admin: Pilih Sekolah Terlebih Dahulu -->
<?php if ($userRole === 'super_admin' && empty($is_sub_module) && empty($hide_pdss_tabs) && empty($allowed_pdss_tabs)): ?>
<div class="alert border-0 rounded-2xl p-4 mb-6 flex items-center gap-4 bg-gradient-to-r from-violet-50 to-indigo-50 border border-violet-100 shadow-sm" style="display: flex;">
    <i class="bi bi-funnel-fill text-xl text-violet-600"></i>
    <div class="flex items-center gap-3 flex-wrap w-full">
        <label for="sa-tenant-select" class="font-semibold text-slate-800 text-sm mb-0 select-none">
            Filter Sekolah (Super Admin):
        </label>
        <select id="sa-tenant-select" name="sa-tenant-select" class="rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs text-slate-700 font-semibold focus:outline-none focus:ring-2 focus:ring-blue-500 max-w-xs">
            <option value="">— Semua Sekolah —</option>
            <?php foreach ($tenantList as $t): ?>
            <option value="<?= htmlspecialchars($t['id']) ?>"
                <?= ($t['id'] === $tenantId ? 'selected' : '') ?>>
                <?= htmlspecialchars($t['nama_sekolah']) ?>
            </option>
            <?php endforeach; ?>
        </select>
        <button class="btn btn-sm btn-primary rounded-xl px-4 py-2 text-xs font-semibold flex items-center gap-1.5 shadow-sm" id="btn-apply-tenant">
            <i class="bi bi-funnel"></i> Terapkan Filter
        </button>
    </div>
</div>
<?php endif; ?>

<!-- Root Vue App Container -->
<div id="pdssApp" v-cloak class="space-y-6">

    <?php if (empty($is_sub_module) && empty($hide_pdss_tabs) && empty($allowed_pdss_tabs)): ?>
    <!-- PAGE HEADER -->
    <div class="flex flex-wrap items-start justify-between gap-4 pt-2 pb-3 mb-6 border-b border-slate-200">
        <div>
            <h2 class="font-bold text-slate-800 text-2xl flex items-center gap-3">
                <span class="inline-flex items-center justify-center rounded-2xl shadow-md w-11 h-11 bg-gradient-to-tr from-blue-500 to-indigo-600 text-white">
                    <i class="bi bi-database-fill text-lg"></i>
                </span>
                PDSS & Pelacakan Karir Alumni
            </h2>
            <p class="text-slate-500 text-sm mt-1">
                Kesiapan pangkalan data sekolah, simulasi kelayakan SNBP, dan penelusuran karir/kuliah alumni.
            </p>
        </div>
        <div class="flex items-center gap-3">
            <button class="btn btn-light border rounded-xl px-4 py-2 text-xs font-semibold flex items-center gap-2 hover:bg-slate-50"
                    @click="refreshAll" :disabled="loading">
                <i class="bi bi-arrow-clockwise" :class="{'animate-spin': loading}"></i>
                Refresh Data
            </button>
        </div>
    </div>
    <?php endif; ?>

    <!-- TABS NAVIGATION -->
    <?php
        $allowed_pdss_tabs = $allowed_pdss_tabs ?? ["kesiapan", "tracking", "config"];
        $hide_pdss_tabs = $hide_pdss_tabs ?? (!empty($is_sub_module) && count($allowed_pdss_tabs) <= 1);
    ?>
    <div class="card border-0 shadow-sm rounded-4 mb-4" <?php if ($hide_pdss_tabs) echo 'style="display:none;"'; ?>>
        <div class="card-body p-2 bg-white rounded-4">
            <div class="nav-tabs-wrapper">
                <ul class="nav nav-tabs border-0 flex-nowrap overflow-x-auto text-nowrap scrollable-nav-tabs gap-3 px-2">
                    <?php if(in_array('kesiapan', $allowed_pdss_tabs)): ?>
                    <li class="nav-item">
                        <button class="nav-link border-0 fw-semibold px-3 py-2.5 fs-7 transition" :class="{'active': activeTab === 'kesiapan'}"
                                @click="activeTab = 'kesiapan'">
                            <i class="bi bi-award-fill me-2 fs-6"></i> Kesiapan & Eligibilitas Siswa
                        </button>
                    </li>
                    <?php endif; ?>
                    <?php if(in_array('tracking', $allowed_pdss_tabs)): ?>
                    <li class="nav-item">
                        <button class="nav-link border-0 fw-semibold px-3 py-2.5 fs-7 transition" :class="{'active': activeTab === 'tracking'}"
                                @click="activeTab = 'tracking'">
                            <i class="bi bi-mortarboard-fill me-2 fs-6"></i> Tracking Alumni & Rekam Kampus
                        </button>
                    </li>
                    <?php endif; ?>
                    <?php if(in_array('master_kampus', $allowed_pdss_tabs)): ?>
                    <li class="nav-item">
                        <button class="nav-link border-0 fw-semibold px-3 py-2.5 fs-7 transition" :class="{'active': activeTab === 'master_kampus'}"
                                @click="activeTab = 'master_kampus'">
                            <i class="bi bi-buildings-fill me-2 fs-6"></i> Master Kampus & Prodi
                        </button>
                    </li>
                    <?php endif; ?>
                    <?php if(in_array('master_jalur', $allowed_pdss_tabs)): ?>
                    <li class="nav-item">
                        <button class="nav-link border-0 fw-semibold px-3 py-2.5 fs-7 transition" :class="{'active': activeTab === 'master_jalur'}"
                                @click="activeTab = 'master_jalur'">
                            <i class="bi bi-signpost-split-fill me-2 fs-6"></i> Master Jalur Masuk
                        </button>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <button class="nav-link border-0 fw-semibold px-3 py-2.5 fs-7 transition" :class="{'active': activeTab === 'simulasi'}"
                                @click="activeTab = 'simulasi'" id="tab-btn-simulasi">
                            <i class="bi bi-journal-check me-2 fs-6"></i> Simulasi Pilihan Kampus
                        </button>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- MAIN SECTIONS -->

    <!-- TAB 1: KESIAPAN & ELIGIBILITAS SISWA (MIGRATED & ENHANCED) -->
    <div v-show="activeTab === 'kesiapan'" class="space-y-6">
        <div v-if="userRole === 'super_admin' && !currentTenantId" class="bg-amber-50 border border-amber-100 rounded-2xl p-8 text-center shadow-sm">
            <div class="w-16 h-16 rounded-full bg-amber-100 text-amber-600 flex items-center justify-center mx-auto mb-4">
                <i class="bi bi-funnel-fill text-2xl"></i>
            </div>
            <h4 class="font-bold text-slate-800 text-base">Pilih Sekolah Terlebih Dahulu</h4>
            <p class="text-slate-500 text-xs mt-1 max-w-sm mx-auto">Silakan pilih sekolah pada filter di bagian atas halaman untuk menampilkan data.</p>
        </div>
        <template v-else>
            <!-- GLOBAL TAHUN AJARAN SELECTOR BAR (BK MANUAL CONTROL) -->
            <div class="bg-white rounded-2xl shadow-xs border border-slate-200/80 p-3 px-4 mb-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center flex-shrink-0">
                            <i class="bi bi-calendar-event text-lg"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-sm text-slate-800 mb-0.5">Tahun Ajaran Evaluasi</h4>
                            <p class="text-xs text-slate-500 mb-0">Pilih tahun ajaran target untuk mengelola data, kuota, lock, dan leger excel secara manual.</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 flex-wrap">
                        <span class="text-xs font-semibold text-slate-600">Pilih Tahun Ajaran:</span>
                        <select v-model="filterAcademicYear" class="rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs text-slate-700 font-bold focus:outline-none focus:ring-2 focus:ring-blue-500 cursor-pointer min-w-[180px]" @change="onAcademicYearChange">
                            <option value="" disabled v-if="academicYears.length === 0">— Belum ada di Master Data —</option>
                            <option value="" disabled v-else>— Pilih Tahun Ajaran —</option>
                            <option v-for="yr in academicYears" :key="yr.id" :value="yr.id">
                                {{ yr.tahun_ajaran }} {{ (yr.is_active === true || yr.is_active == 1 || yr.is_active === 't') ? '(Aktif)' : '' }}
                            </option>
                        </select>
                        <a v-if="academicYears.length === 0" href="<?= $baseUrl ?? '' ?>/master-data" class="text-xs text-blue-600 hover:text-blue-800 font-medium underline flex items-center gap-1">
                            <i class="bi bi-box-arrow-up-right"></i> Buka Master Data
                        </a>
                    </div>
                </div>
            </div>

            <!-- STATUS LOCK DATA KESIAPAN (4 LANGKAH BERTAHAP) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
                <!-- Lock Langkah 1 -->
                <div class="bg-white rounded-2xl shadow-xs border p-4 flex flex-col justify-between gap-3 transition hover:shadow-sm h-full"
                     :class="locks[1]?.is_locked ? 'border-amber-200 bg-amber-50/20' : 'border-slate-200/80'">
                    <div class="flex items-start gap-3">
                        <div class="w-9 h-9 rounded-xl flex items-center justify-center text-sm flex-shrink-0"
                             :class="locks[1]?.is_locked ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700'">
                            <i class="bi" :class="locks[1]?.is_locked ? 'bi-lock-fill' : 'bi-unlock-fill'"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-xs text-slate-800 uppercase tracking-wider mb-1">1. Mapel PDSS</h4>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-md inline-block" :class="locks[1]?.is_locked ? 'bg-amber-100 text-amber-800 border border-amber-200' : 'bg-emerald-100 text-emerald-800 border border-emerald-200'">
                                {{ locks[1]?.is_locked ? 'TERKUNCI' : 'TERBUKA' }}
                            </span>
                            <p class="text-[10px] text-slate-500 mt-1 mb-0" v-if="locks[1]?.is_locked">
                                Dikunci: {{ locks[1]?.locked_by }}
                            </p>
                        </div>
                    </div>
                    <div v-if="canWrite" class="text-end pt-1">
                        <button class="btn btn-xs font-semibold text-[10px] px-2.5 py-1 rounded-lg transition"
                                :class="locks[1]?.is_locked ? 'btn-outline-warning text-amber-800 hover:bg-amber-50' : 'btn-light border text-slate-700 hover:bg-slate-100'"
                                @click="togglePdssLock(1)">
                            <i class="bi me-1" :class="locks[1]?.is_locked ? 'bi-unlock-fill' : 'bi-lock-fill'"></i>
                            {{ locks[1]?.is_locked ? 'Buka Kunci' : 'Kunci Step 1' }}
                        </button>
                    </div>
                </div>

                <!-- Lock Langkah 2 -->
                <div class="bg-white rounded-2xl shadow-xs border p-4 flex flex-col justify-between gap-3 transition hover:shadow-sm h-full"
                     :class="locks[2]?.is_locked ? 'border-amber-200 bg-amber-50/20' : 'border-slate-200/80'">
                    <div class="flex items-start gap-3">
                        <div class="w-9 h-9 rounded-xl flex items-center justify-center text-sm flex-shrink-0"
                             :class="locks[2]?.is_locked ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700'">
                            <i class="bi" :class="locks[2]?.is_locked ? 'bi-lock-fill' : 'bi-unlock-fill'"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-xs text-slate-800 uppercase tracking-wider mb-1">2. Nilai Rapor</h4>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-md inline-block" :class="locks[2]?.is_locked ? 'bg-amber-100 text-amber-800 border border-amber-200' : 'bg-emerald-100 text-emerald-800 border border-emerald-200'">
                                {{ locks[2]?.is_locked ? 'TERKUNCI' : 'TERBUKA' }}
                            </span>
                            <p class="text-[10px] text-slate-500 mt-1 mb-0" v-if="locks[2]?.is_locked">
                                Dikunci: {{ locks[2]?.locked_by }}
                            </p>
                        </div>
                    </div>
                    <div v-if="canWrite" class="text-end pt-1">
                        <button class="btn btn-xs font-semibold text-[10px] px-2.5 py-1 rounded-lg transition"
                                :class="locks[2]?.is_locked ? 'btn-outline-warning text-amber-800 hover:bg-amber-50' : 'btn-light border text-slate-700 hover:bg-slate-100'"
                                @click="togglePdssLock(2)">
                            <i class="bi me-1" :class="locks[2]?.is_locked ? 'bi-unlock-fill' : 'bi-lock-fill'"></i>
                            {{ locks[2]?.is_locked ? 'Buka Kunci' : 'Kunci Step 2' }}
                        </button>
                    </div>
                </div>

                <!-- Lock Langkah 3 -->
                <div class="bg-white rounded-2xl shadow-xs border p-4 flex flex-col justify-between gap-3 transition hover:shadow-sm h-full"
                     :class="locks[3]?.is_locked ? 'border-amber-200 bg-amber-50/20' : 'border-slate-200/80'">
                    <div class="flex items-start gap-3">
                        <div class="w-9 h-9 rounded-xl flex items-center justify-center text-sm flex-shrink-0"
                             :class="locks[3]?.is_locked ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700'">
                            <i class="bi" :class="locks[3]?.is_locked ? 'bi-lock-fill' : 'bi-unlock-fill'"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-xs text-slate-800 uppercase tracking-wider mb-1">3. Ranking & Kuota</h4>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-md inline-block" :class="locks[3]?.is_locked ? 'bg-amber-100 text-amber-800 border border-amber-200' : 'bg-emerald-100 text-emerald-800 border border-emerald-200'">
                                {{ locks[3]?.is_locked ? 'TERKUNCI' : 'TERBUKA' }}
                            </span>
                            <p class="text-[10px] text-slate-500 mt-1 mb-0" v-if="locks[3]?.is_locked">
                                Dikunci: {{ locks[3]?.locked_by }}
                            </p>
                        </div>
                    </div>
                    <div v-if="canWrite" class="text-end pt-1">
                        <button class="btn btn-xs font-semibold text-[10px] px-2.5 py-1 rounded-lg transition"
                                :class="locks[3]?.is_locked ? 'btn-outline-warning text-amber-800 hover:bg-amber-50' : 'btn-light border text-slate-700 hover:bg-slate-100'"
                                @click="togglePdssLock(3)">
                            <i class="bi me-1" :class="locks[3]?.is_locked ? 'bi-unlock-fill' : 'bi-lock-fill'"></i>
                            {{ locks[3]?.is_locked ? 'Buka Kunci' : 'Kunci Step 3' }}
                        </button>
                    </div>
                </div>

                <!-- Lock Langkah 4 -->
                <div class="bg-white rounded-2xl shadow-xs border p-4 flex flex-col justify-between gap-3 transition hover:shadow-sm h-full"
                     :class="locks[4]?.is_locked ? 'border-amber-200 bg-amber-50/20' : 'border-slate-200/80'">
                    <div class="flex items-start gap-3">
                        <div class="w-9 h-9 rounded-xl flex items-center justify-center text-sm flex-shrink-0"
                             :class="locks[4]?.is_locked ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700'">
                            <i class="bi" :class="locks[4]?.is_locked ? 'bi-lock-fill' : 'bi-unlock-fill'"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-xs text-slate-800 uppercase tracking-wider mb-1">4. Final Eligible</h4>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-md inline-block" :class="locks[4]?.is_locked ? 'bg-amber-100 text-amber-800 border border-amber-200' : 'bg-emerald-100 text-emerald-800 border border-emerald-200'">
                                {{ locks[4]?.is_locked ? 'TERKUNCI' : 'TERBUKA' }}
                            </span>
                            <p class="text-[10px] text-slate-500 mt-1 mb-0" v-if="locks[4]?.is_locked">
                                Dikunci: {{ locks[4]?.locked_by }}
                            </p>
                        </div>
                    </div>
                    <div v-if="canWrite" class="text-end pt-1">
                        <button class="btn btn-xs font-semibold text-[10px] px-2.5 py-1 rounded-lg transition"
                                :class="locks[4]?.is_locked ? 'btn-outline-warning text-amber-800 hover:bg-amber-50' : 'btn-light border text-slate-700 hover:bg-slate-100'"
                                @click="togglePdssLock(4)">
                            <i class="bi me-1" :class="locks[4]?.is_locked ? 'bi-unlock-fill' : 'bi-lock-fill'"></i>
                            {{ locks[4]?.is_locked ? 'Buka Kunci' : 'Kunci Final' }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- KPI Row -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-4">
                <div class="bg-white rounded-2xl shadow-xs border border-slate-200/80 p-4 flex items-center justify-between transition hover:shadow-sm">
                    <div>
                        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Siswa Kelas 12</span>
                        <span class="text-2xl font-bold text-slate-800 mt-1 block">{{ stats.totalStudents }}</span>
                        <span class="text-xs text-slate-500 mt-1 block">Aktif dalam database</span>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center shadow-2xs border border-blue-100">
                        <i class="bi bi-people-fill text-xl"></i>
                    </div>
                </div>
                
                <div class="bg-white rounded-2xl shadow-xs border border-slate-200/80 p-4 flex items-center justify-between transition hover:shadow-sm">
                    <div>
                        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Kelengkapan Rapor</span>
                        <span class="text-2xl font-bold text-slate-800 mt-1 block">{{ stats.completenessRate }}%</span>
                        <span class="text-xs text-slate-500 mt-1 block">{{ stats.studentsWithGrades }} siswa terisi nilai</span>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center shadow-2xs border border-emerald-100">
                        <i class="bi bi-file-earmark-check-fill text-xl"></i>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-xs border border-slate-200/80 p-4 flex items-center justify-between transition hover:shadow-sm">
                    <div>
                        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Siswa Eligible SNBP</span>
                        <span class="text-2xl font-bold text-slate-800 mt-1 block">{{ stats.eligibleCount }}</span>
                        <span class="text-xs text-slate-500 mt-1 block">Berdasarkan simulasi kuota {{ quotaPercent }}%</span>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center shadow-2xs border border-amber-100">
                        <i class="bi bi-award-fill text-xl"></i>
                    </div>
                </div>
            </div>

            <!-- ALUR KERJA 4 LANGKAH BK (Workflow Accordion / Cards) -->
            <div class="grid grid-cols-1 gap-4">
                
                <!-- LANGKAH 1: KONFIGURASI MATA PELAJARAN PDSS -->
                <div class="bg-white rounded-2xl shadow-xs border border-slate-200/80 overflow-hidden">
                    <div class="px-5 py-4 bg-slate-50/80 border-b border-slate-200/80 flex items-center justify-between cursor-pointer hover:bg-slate-100/60 transition" @click="showMapelConfig = !showMapelConfig">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-sm">1</div>
                            <div>
                                <h4 class="font-bold text-sm text-slate-800 mb-0.5">Langkah 1: Tentukan Mata Pelajaran untuk PDSS</h4>
                                <p class="text-xs text-slate-500 mb-0">Pilih mata pelajaran dari kurikulum yang akan dihitung nilainya selama semester 1 s.d. 5/6.</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <span v-if="locks[1]?.is_locked" class="bg-amber-100 text-amber-800 text-[10px] font-bold py-1 px-2 rounded-lg flex items-center gap-1 select-none">
                                <i class="bi bi-lock-fill"></i> TERKUNCI
                            </span>
                            <i class="bi" :class="showMapelConfig ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
                        </div>
                    </div>
                    
                    <div v-show="showMapelConfig" class="p-5 border-t border-slate-100 animate-fade-in">
                        <div v-if="loadingMapels" class="text-center py-4 text-slate-400 text-xs">
                            <div class="spinner-border spinner-border-sm text-primary me-2"></div>
                            Memuat daftar mata pelajaran...
                        </div>
                        <div v-else-if="pdssMapels.length === 0" class="text-center py-4 text-slate-400 text-xs">
                            Belum ada mata pelajaran terdaftar di sekolah ini. Silakan tambahkan di menu Buku Induk / Mata Pelajaran.
                        </div>
                        <div v-else>
                            <!-- Action Presets & Filter Toolbar (Kurikulum Merdeka - Clean Ergonomic Layout) -->
                            <div class="mb-4 bg-slate-50/80 p-3.5 px-4 rounded-2xl border border-slate-200/80 space-y-3">
                                <!-- Baris 1: Filter Kategori, Pencarian, & Tombol Sakti Deteksi Otomatis -->
                                <div class="flex flex-wrap items-center justify-between gap-3">
                                    <div class="flex items-center gap-3 flex-wrap">
                                        <!-- Filter Kategori -->
                                        <div class="flex items-center gap-2">
                                            <span class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-700 whitespace-nowrap">
                                                <i class="bi bi-funnel-fill text-blue-600"></i> Kategori:
                                            </span>
                                            <select v-model="filterMapelCategory" class="form-select form-select-sm text-xs rounded-xl border-slate-300 py-1.5 px-3 font-semibold bg-white shadow-2xs" style="min-width: 170px;">
                                                <option value="">— Semua Kategori —</option>
                                                <option v-for="cat in mapelCategories" :key="cat" :value="cat">
                                                    {{ cat }}
                                                </option>
                                            </select>
                                        </div>

                                        <!-- Pencarian Mapel -->
                                        <div class="relative flex items-center">
                                            <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs pointer-events-none"></i>
                                            <input type="text" v-model="searchMapelConfig" placeholder="Cari nama mapel..." class="rounded-xl border border-slate-300 bg-white py-1.5 pr-3 text-xs text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500 w-52 shadow-2xs" style="padding-left: 2.25rem !important;">
                                        </div>
                                    </div>

                                    <!-- Tombol Utama Deteksi Otomatis (High Contrast & Sharp) -->
                                    <button type="button" 
                                            class="btn btn-sm text-white font-bold rounded-xl px-4 py-2 flex items-center gap-2 shadow-sm transition-all hover:opacity-95"
                                            style="background-color: #059669; border-color: #047857; color: #ffffff !important;"
                                            :disabled="locks[1]?.is_locked" 
                                            @click="autoDetectMapelsFromGrades">
                                        <i class="bi bi-lightning-charge-fill text-amber-300"></i>
                                        <span>Deteksi Otomatis dari Rapor</span>
                                    </button>
                                </div>

                                <!-- Baris 2: Preset Pilihan Cepat & Status -->
                                <div class="flex flex-wrap items-center justify-between gap-3 pt-2.5 border-t border-slate-200/80">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-600 me-1 whitespace-nowrap">
                                            <i class="bi bi-sliders text-indigo-600"></i> Pilihan Cepat:
                                        </span>
                                        <button type="button" class="btn btn-sm btn-primary rounded-xl text-xs font-semibold px-3 py-1.5 flex items-center gap-1.5 shadow-2xs" :disabled="locks[1]?.is_locked" @click="applySnbpPreset">
                                            <i class="bi bi-award-fill text-amber-300"></i> 6 Mapel Wajib SNBP
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-primary rounded-xl text-xs font-semibold px-3 py-1.5 flex items-center gap-1.5" :disabled="locks[1]?.is_locked" @click="selectAllMapels([1,2,3,4,5])">
                                            <i class="bi bi-check-all"></i> Pilih Semua (Sem 1-5)
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger rounded-xl text-xs font-semibold px-3 py-1.5 flex items-center gap-1.5" :disabled="locks[1]?.is_locked" @click="clearAllMapels">
                                            <i class="bi bi-x-circle"></i> Kosongkan
                                        </button>
                                    </div>

                                    <div class="badge bg-white text-slate-700 border border-slate-200 px-3 py-1.5 rounded-xl font-bold text-[11px] shadow-2xs flex items-center gap-1.5">
                                        <i class="bi bi-check2-circle text-emerald-600"></i>
                                        <span>Dicentang: <strong class="text-blue-700">{{ totalConfiguredMapels }}</strong> Mapel Aktif</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Table Grid Semester Mapel (Kurikulum Merdeka Enhanced - 100% Responsive) -->
                            <div class="table-responsive mb-3 rounded-xl border border-slate-200/80 shadow-2xs bg-white overflow-x-auto">
                                <table class="table table-bordered align-middle text-xs mb-0 w-full" style="min-width: 700px;">
                                    <thead class="bg-slate-50 text-center font-bold text-slate-700">
                                        <tr class="border-b border-slate-200">
                                            <th rowspan="2" class="align-middle text-start ps-3 py-2.5 bg-slate-100/70" style="width: 25%;">Nama Mata Pelajaran</th>
                                            <th rowspan="2" class="align-middle text-start ps-2 py-2.5 bg-slate-100/70" style="width: 17%;">Kategori</th>
                                            <th colspan="2" class="py-2 bg-blue-50/80 text-blue-900 border-l border-blue-200 text-[11px]" style="width: 19%;">Kelas X (Fase E)</th>
                                            <th colspan="2" class="py-2 bg-indigo-50/80 text-indigo-900 border-l border-indigo-200 text-[11px]" style="width: 19%;">Kelas XI (Fase F)</th>
                                            <th colspan="2" class="py-2 bg-purple-50/80 text-purple-900 border-l border-purple-200 text-[11px]" style="width: 20%;">Kelas XII (Fase F)</th>
                                        </tr>
                                        <tr class="bg-slate-50 text-[10px] font-semibold text-slate-600">
                                            <th class="py-1 border-l border-slate-200" style="width: 9.5%;">Sem 1</th>
                                            <th class="py-1" style="width: 9.5%;">Sem 2</th>
                                            <th class="py-1 border-l border-slate-200" style="width: 9.5%;">Sem 3</th>
                                            <th class="py-1" style="width: 9.5%;">Sem 4</th>
                                            <th class="py-1 border-l border-slate-200" style="width: 10%;">Sem 5</th>
                                            <th class="py-1" style="width: 10%;">Sem 6</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="mapel in filteredPdssMapels" :key="mapel.id" class="transition-colors hover:bg-slate-50/80" :class="{'bg-blue-50/20 font-medium': mapel.sem_1 || mapel.sem_2 || mapel.sem_3 || mapel.sem_4 || mapel.sem_5}">
                                            <td class="font-semibold text-slate-800 ps-3 py-2">
                                                <div class="flex items-center gap-1.5 truncate">
                                                    <i class="bi bi-journal-text text-slate-400 text-xs"></i>
                                                    <span class="truncate" :title="mapel.nama_mapel">{{ mapel.nama_mapel }}</span>
                                                </div>
                                            </td>
                                            <td class="text-slate-600 ps-2 py-2">
                                                <span class="badge rounded-md font-semibold text-[9px] px-2 py-0.5 inline-block truncate max-w-full"
                                                      :class="getMapelCategoryBadge(mapel.kategori)" :title="mapel.kategori">
                                                    {{ mapel.kategori || 'Wajib' }}
                                                </span>
                                            </td>
                                            <!-- Sem 1 -->
                                            <td class="text-center py-1.5 border-l">
                                                <input type="checkbox" v-model="mapel.sem_1" :disabled="locks[1]?.is_locked" class="rounded text-blue-600 focus:ring-blue-500 w-3.5 h-3.5 cursor-pointer">
                                                <span v-if="mapel.has_sem_1" class="text-[9px] text-emerald-600 font-semibold block leading-none mt-0.5">● Ada</span>
                                                <span v-else class="text-[8px] text-slate-300 block leading-none mt-0.5">-</span>
                                            </td>
                                            <!-- Sem 2 -->
                                            <td class="text-center py-1.5">
                                                <input type="checkbox" v-model="mapel.sem_2" :disabled="locks[1]?.is_locked" class="rounded text-blue-600 focus:ring-blue-500 w-3.5 h-3.5 cursor-pointer">
                                                <span v-if="mapel.has_sem_2" class="text-[9px] text-emerald-600 font-semibold block leading-none mt-0.5">● Ada</span>
                                                <span v-else class="text-[8px] text-slate-300 block leading-none mt-0.5">-</span>
                                            </td>
                                            <!-- Sem 3 -->
                                            <td class="text-center py-1.5 border-l">
                                                <input type="checkbox" v-model="mapel.sem_3" :disabled="locks[1]?.is_locked" class="rounded text-indigo-600 focus:ring-indigo-500 w-3.5 h-3.5 cursor-pointer">
                                                <span v-if="mapel.has_sem_3" class="text-[9px] text-emerald-600 font-semibold block leading-none mt-0.5">● Ada</span>
                                                <span v-else class="text-[8px] text-slate-300 block leading-none mt-0.5">-</span>
                                            </td>
                                            <!-- Sem 4 -->
                                            <td class="text-center py-1.5">
                                                <input type="checkbox" v-model="mapel.sem_4" :disabled="locks[1]?.is_locked" class="rounded text-indigo-600 focus:ring-indigo-500 w-3.5 h-3.5 cursor-pointer">
                                                <span v-if="mapel.has_sem_4" class="text-[9px] text-emerald-600 font-semibold block leading-none mt-0.5">● Ada</span>
                                                <span v-else class="text-[8px] text-slate-300 block leading-none mt-0.5">-</span>
                                            </td>
                                            <!-- Sem 5 -->
                                            <td class="text-center py-1.5 border-l">
                                                <input type="checkbox" v-model="mapel.sem_5" :disabled="locks[1]?.is_locked" class="rounded text-purple-600 focus:ring-purple-500 w-3.5 h-3.5 cursor-pointer">
                                                <span v-if="mapel.has_sem_5" class="text-[9px] text-emerald-600 font-semibold block leading-none mt-0.5">● Ada</span>
                                                <span v-else class="text-[8px] text-slate-300 block leading-none mt-0.5">-</span>
                                            </td>
                                            <!-- Sem 6 -->
                                            <td class="text-center py-1.5">
                                                <input type="checkbox" v-model="mapel.sem_6" :disabled="locks[1]?.is_locked" class="rounded text-purple-600 focus:ring-purple-500 w-3.5 h-3.5 cursor-pointer">
                                                <span v-if="mapel.has_sem_6" class="text-[9px] text-emerald-600 font-semibold block leading-none mt-0.5">● Ada</span>
                                                <span v-else class="text-[8px] text-slate-300 block leading-none mt-0.5">-</span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            
                            <div class="flex justify-end gap-2 border-t pt-3">
                                <button class="btn btn-sm btn-light border font-semibold px-4 py-1.5 rounded-xl text-xs" @click="showMapelConfig = false">
                                    Tutup
                                </button>
                                <button class="btn btn-sm btn-primary font-semibold px-4 py-1.5 rounded-xl text-xs flex items-center gap-1.5 shadow-sm" :disabled="savingMapels || locks[1]?.is_locked" @click="savePdssMapels">
                                    <span v-if="savingMapels" class="spinner-border spinner-border-sm me-1"></span>
                                    <i class="bi bi-save"></i> Simpan Pilihan Mapel
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- LANGKAH 2: PENGECEKAN NILAI RAPOR DARI BUKU INDUK & LEGER -->
                <div class="bg-white rounded-2xl shadow-xs border border-slate-200/80 overflow-hidden">
                    <div class="px-5 py-4 bg-slate-50/80 border-b border-slate-200/80 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-teal-50 text-teal-600 flex items-center justify-center font-bold text-sm">2</div>
                            <div>
                                <h4 class="font-bold text-sm text-slate-800 mb-0.5">Langkah 2: Pengecekan Nilai Rapor Siswa &amp; Unduh Leger Nilai</h4>
                                <p class="text-xs text-slate-500 mb-0">Nilai diambil otomatis dari Buku Induk siswa kelas 12 aktif. Periksa kelengkapan nilai sebelum mengunci.</p>
                            </div>
                        </div>
                        <span v-if="locks[2]?.is_locked" class="bg-amber-100 text-amber-800 text-[10px] font-bold py-1 px-2 rounded-lg flex items-center gap-1 select-none">
                            <i class="bi bi-lock-fill"></i> TERKUNCI
                        </span>
                    </div>
                    
                    <div class="p-4 px-5 border-t border-slate-100">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0">
                                    <i class="bi bi-file-earmark-check-fill text-base"></i>
                                </div>
                                <div>
                                    <span class="text-xs text-slate-500 font-medium block">Status Kelengkapan Nilai Siswa:</span>
                                    <span class="badge font-bold text-xs" :class="stats.completenessRate >= 100 ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' : 'bg-amber-100 text-amber-800 border border-amber-200'">
                                        {{ stats.completenessRate }}% Lengkap ({{ stats.studentsWithGrades }} dari {{ stats.totalStudents }} Siswa)
                                    </span>
                                </div>
                            </div>

                            <div class="flex items-center gap-2 flex-wrap">
                                <!-- Download Leger by Semester -->
                                <div class="flex items-center gap-2 bg-slate-50 border border-slate-200 rounded-xl px-3 py-1.5 text-xs">
                                    <span class="text-slate-600 font-semibold select-none">Unduh Leger:</span>
                                    <select v-model="downloadSemester" class="border-0 bg-transparent py-0 px-1 text-xs text-slate-700 font-bold focus:ring-0 focus:outline-none cursor-pointer">
                                        <option :value="1">Semester 1 (Kelas X)</option>
                                        <option :value="2">Semester 2 (Kelas X)</option>
                                        <option :value="3">Semester 3 (Kelas XI)</option>
                                        <option :value="4">Semester 4 (Kelas XI)</option>
                                        <option :value="5">Semester 5 (Kelas XII)</option>
                                        <option :value="6">Semester 6 (Kelas XII)</option>
                                    </select>
                                    <a v-if="!mapelNotConfigured && students.length > 0 && filterAcademicYear" 
                                       :href="baseUrl + '/api/v1/pdss/download-leger?semester=' + downloadSemester + '&tahun_ajaran_id=' + filterAcademicYear + (currentTenantId ? '&tenant_id=' + currentTenantId : '')" 
                                       class="btn btn-sm btn-primary rounded-lg px-3 py-1 text-xs font-semibold flex items-center gap-1.5 transition ms-1"
                                       target="_blank">
                                        <i class="bi bi-file-earmark-excel-fill text-xs text-emerald-300"></i> Unduh Excel
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- LANGKAH 3: ATUR KUOTA & HITUNG RANKING PARALEL -->
                <div class="bg-white rounded-2xl shadow-xs border border-slate-200/80 overflow-hidden">
                    <div class="px-5 py-4 bg-slate-50/80 border-b border-slate-200/80 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold text-sm">3</div>
                            <div>
                                <h4 class="font-bold text-sm text-slate-800 mb-0.5">Langkah 3: Atur Kuota Eligible &amp; Hitung Ranking Paralel Jurusan</h4>
                                <p class="text-xs text-slate-500 mb-0">Sesuaikan persentase kuota berdasarkan akreditasi sekolah (+5% e-Rapor) dan simpan ranking paralel.</p>
                            </div>
                        </div>
                        <span v-if="locks[3]?.is_locked" class="bg-amber-100 text-amber-800 text-[10px] font-bold py-1 px-2 rounded-lg flex items-center gap-1 select-none">
                            <i class="bi bi-lock-fill"></i> TERKUNCI
                        </span>
                    </div>
                    
                    <div class="p-4 px-5 border-t border-slate-100 space-y-3.5">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <!-- Accreditation Info -->
                            <div class="bg-slate-50 rounded-xl px-3.5 py-1.5 border border-slate-200 flex items-center gap-2.5">
                                <span class="text-xs text-slate-500">Akreditasi Sekolah:</span>
                                <span class="badge bg-blue-600 text-white font-bold py-1 px-2 rounded-lg text-xs">{{ accreditation }}</span>
                                <span class="text-xs text-slate-400 border-l pl-2 font-medium" v-if="accreditation.includes('A')">Rekomendasi: 40%</span>
                                <span class="text-xs text-slate-400 border-l pl-2 font-medium" v-else-if="accreditation.includes('B')">Rekomendasi: 25%</span>
                                <span class="text-xs text-slate-400 border-l pl-2 font-medium" v-else>Rekomendasi: 5%</span>
                            </div>

                            <!-- Quota Selector -->
                            <div class="flex items-center gap-3 flex-wrap">
                                <div class="flex items-center gap-2">
                                    <label for="quota-select" class="text-xs font-semibold text-slate-600">Kuota SNBP:</label>
                                    <select id="quota-select" v-model="quotaPercent" :disabled="locks[3]?.is_locked" class="rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs text-slate-700 font-semibold focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <option :value="40">40% (Akreditasi A - Standar)</option>
                                        <option :value="45">45% (Akreditasi A + e-Rapor)</option>
                                        <option :value="25">25% (Akreditasi B - Standar)</option>
                                        <option :value="30">30% (Akreditasi B + e-Rapor)</option>
                                        <option :value="5">5% (Akreditasi C - Standar)</option>
                                        <option :value="10">10% (Akreditasi C + e-Rapor)</option>
                                        <option :value="15">15%</option>
                                        <option :value="50">50%</option>
                                    </select>
                                </div>
                                <label class="flex items-center gap-1.5 text-xs font-semibold text-slate-600 cursor-pointer select-none">
                                    <input type="checkbox" v-model="useERapor" :disabled="locks[3]?.is_locked" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                    Menggunakan e-Rapor (+5% Kuota SNBP)
                                </label>
                            </div>
                        </div>

                        <!-- Live Filters & Action Buttons -->
                        <div class="bg-slate-50/70 p-3 rounded-xl border border-slate-200/80 flex flex-wrap items-center justify-between gap-3">
                            <div class="flex flex-wrap gap-2 items-center">
                                <div class="position-relative">
                                    <i class="bi bi-search position-absolute text-slate-400 text-xs" style="left: 12px; top: 50%; transform: translateY(-50%);"></i>
                                    <input type="text" v-model="searchStudent" placeholder="Cari nama / NISN..." class="rounded-xl border border-slate-200 pl-8 pr-3 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500 w-44">
                                </div>
                                <select v-model="filterClass" class="rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs text-slate-600 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="">Semua Kelas</option>
                                    <option v-for="cls in uniqueClasses" :key="cls" :value="cls">{{ cls }}</option>
                                </select>
                                <select v-model="filterMajor" class="rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs text-slate-600 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="">Semua Jurusan</option>
                                    <option v-for="maj in uniqueMajors" :key="maj" :value="maj">{{ maj }}</option>
                                </select>
                            </div>

                            <div class="flex items-center gap-2 flex-wrap">
                                <!-- Reset Siswa Eligible Button -->
                                <button type="button" 
                                        class="btn btn-sm btn-outline-danger rounded-xl text-xs font-bold px-3 py-1.5 flex items-center gap-1.5 shadow-2xs transition-all hover:bg-rose-50" 
                                        :disabled="recalculating || locks[3]?.is_locked" 
                                        title="Kembalikan status seluruh siswa eligible ke kalkulasi otomatis kuota & nilai rapor" 
                                        @click="resetAllEligible">
                                    <i class="bi bi-arrow-counterclockwise"></i>
                                    <span>Reset Eligible</span>
                                </button>

                                <button type="button" class="btn btn-sm btn-primary rounded-xl text-xs font-semibold px-3 py-1.5 flex items-center gap-1.5 shadow-sm" :disabled="recalculating || locks[3]?.is_locked" @click="recalculateRanking">
                                    <i class="bi bi-arrow-repeat" :class="{'animate-spin': recalculating}"></i>
                                    <span>Hitung Ulang &amp; Simpan</span>
                                </button>
                                <a v-if="filterAcademicYear && students.length > 0" 
                                   :href="baseUrl + '/api/v1/pdss/export-snbp?tahun_ajaran_id=' + filterAcademicYear + (currentTenantId ? '&tenant_id=' + currentTenantId : '')" 
                                   class="btn btn-sm text-white font-bold rounded-xl px-3.5 py-1.5 text-xs flex items-center gap-1.5 shadow-sm transition-all hover:opacity-95"
                                   style="background-color: #059669 !important; border-color: #047857 !important; color: #ffffff !important;"
                                   target="_blank">
                                    <i class="bi bi-file-earmark-spreadsheet-fill text-white"></i>
                                    <span style="color: #ffffff !important;">Export Rekap SNBP (.xlsx)</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- LANGKAH 4: FINALISASI SISWA ELIGIBLE & PENGUNDURAN DIRI -->
                <div class="bg-white rounded-2xl shadow-xs border border-slate-200/80 overflow-hidden">
                    <div class="px-5 py-4 bg-slate-50/80 border-b border-slate-200/80 flex flex-wrap items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-sm">4</div>
                            <div>
                                <h4 class="font-bold text-sm text-slate-800 mb-0.5">Langkah 4: Tinjau Kelayakan, Pengunduran Diri &amp; Finalisasi Eligible</h4>
                                <p class="text-xs text-slate-500 mb-0">Siswa eligible yang mengundurkan diri wajib mengunggah Surat Pernyataan. Kuota otomatis dilimpahkan ke ranking bawahnya.</p>
                            </div>
                        </div>
                        <span v-if="locks[4]?.is_locked" class="bg-amber-100 text-amber-800 text-[10px] font-bold py-1.5 px-2.5 rounded-lg flex items-center gap-1 select-none">
                            <i class="bi bi-lock-fill"></i> FINAL TERKUNCI
                        </span>
                    </div>

                    <!-- Table content -->
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr class="bg-slate-50/80 border-b border-slate-200/80 text-xs text-slate-600 font-bold uppercase tracking-wider">
                                    <th class="ps-5 py-3 text-center" style="width: 70px;">Rank</th>
                                    <th class="py-3" style="min-width: 220px;">Nama Lengkap</th>
                                    <th class="py-3" style="width: 120px;">NISN</th>
                                    <th class="py-3" style="width: 100px;">Kelas</th>
                                    <th class="py-3" style="width: 120px;">Jurusan</th>
                                    <th class="py-3 text-center" style="width: 110px;">Rata-rata</th>
                                    <th class="py-3 text-center" style="width: 110px;">Kelengkapan</th>
                                    <th class="py-3 text-center" style="width: 160px;">Status Kelayakan</th>
                                    <th class="py-3 text-center pe-5" style="width: 190px;">Aksi BK / Pengunduran Diri</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="loading">
                                    <td colspan="9" class="text-center py-10 text-slate-400 text-xs">
                                         <div class="spinner-border spinner-border-sm text-primary me-2"></div>
                                         Memuat simulasi data siswa...
                                    </td>
                                </tr>
                                <tr v-else-if="filteredStudents.length === 0">
                                    <td colspan="9" class="text-center py-10 text-slate-400 text-xs">
                                         Tidak ada data siswa kelas 12 yang terdeteksi dengan kriteria ini.
                                    </td>
                                </tr>
                                <tr v-else v-for="stu in filteredStudents" :key="stu.id" class="text-xs border-b border-slate-100 transition-colors hover:bg-slate-50/80" :class="{'bg-rose-50/30': stu.is_resigned, 'bg-blue-50/20': stu.is_eligible}">
                                    <td class="text-center ps-5 py-2.5">
                                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-full font-bold text-xs"
                                              :class="{
                                                  'bg-blue-100 text-blue-700 shadow-2xs': stu.is_eligible,
                                                  'bg-rose-100 text-rose-600 line-through': stu.is_resigned,
                                                  'bg-slate-100 text-slate-500': !stu.is_eligible && !stu.is_resigned
                                              }">
                                            #{{ stu.majorRank }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="flex items-center gap-2">
                                            <button class="btn btn-sm btn-light border p-1 rounded-lg text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 transition" title="Audit Detail Nilai Rapor" @click="showAuditModal(stu.id)">
                                                <i class="bi bi-eye-fill text-xs"></i>
                                            </button>
                                            <div>
                                                <span class="font-bold text-slate-800 block text-xs" :class="{'line-through text-slate-400': stu.is_resigned}">{{ stu.nama_lengkap }}</span>
                                                <div class="flex items-center gap-1 mt-0.5">
                                                    <span v-if="stu.is_resigned" class="badge bg-rose-100 text-rose-700 text-[9px] px-1.5 py-0.2 rounded font-semibold">Mundur</span>
                                                    <span v-else-if="stu.is_replacement" class="badge bg-indigo-100 text-indigo-700 text-[9px] px-1.5 py-0.2 rounded font-semibold">Pelimpahan</span>
                                                    <span v-if="stu.is_retained" class="badge bg-purple-100 text-purple-700 text-[9px] px-1.5 py-0.2 rounded font-semibold" title="Siswa memiliki riwayat mengulang semester"><i class="bi bi-arrow-repeat"></i> Mengulang</span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="font-mono text-xs text-slate-500">{{ stu.nisn || '—' }}</td>
                                    <td class="text-slate-600 font-medium">{{ stu.nama_kelas || '—' }}</td>
                                    <td class="text-slate-600 font-medium">{{ stu.nama_jurusan || '—' }}</td>
                                    <td class="text-center font-bold" :class="{'text-slate-800': stu.rata_rata > 0, 'text-slate-300': stu.rata_rata === 0}">
                                        {{ stu.rata_rata > 0 ? stu.rata_rata.toFixed(2) : '—' }}
                                    </td>
                                    <td class="text-center">
                                        <span class="text-xs px-2.5 py-0.5 rounded-md font-semibold"
                                              :class="stu.jumlah_nilai >= (stu.expected_nilai || totalConfiguredSemesters) ? 'bg-emerald-50 text-emerald-700 border border-emerald-200/60' : 'bg-amber-50 text-amber-700 border border-amber-200/60'">
                                            {{ stu.jumlah_nilai }} / {{ stu.expected_nilai || totalConfiguredSemesters }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span v-if="stu.is_resigned" class="badge bg-slate-200 text-slate-700 border border-slate-300 text-[10px] px-2.5 py-1 rounded-lg font-bold uppercase block w-100">
                                            MENGUNDURKAN DIRI
                                        </span>
                                        <span v-else class="text-[10px] px-2.5 py-1 rounded-lg font-bold uppercase block w-100 text-center"
                                              :class="stu.is_eligible ? 'bg-emerald-100 text-emerald-800 border border-emerald-300' : 'bg-rose-50 text-rose-700 border border-rose-200'">
                                            {{ stu.is_eligible ? (stu.is_replacement ? 'ELIGIBLE (PELIMPAHAN)' : 'ELIGIBLE') : 'TIDAK ELIGIBLE' }}
                                            <span v-if="stu.status_eligible !== 'auto'" class="text-[9px] text-slate-400 block font-normal">(BK Manual)</span>
                                        </span>
                                    </td>
                                    <td class="text-center pe-5">
                                        <div class="flex items-center justify-center gap-1.5 flex-wrap">
                                            <!-- Resigned actions -->
                                            <template v-if="stu.is_resigned">
                                                <button class="btn btn-xs btn-outline-danger px-2.5 py-1 rounded-lg text-[10px] font-bold flex items-center gap-1"
                                                        @click="viewSuratPengunduranDiri(stu)">
                                                    <i class="bi bi-file-earmark-pdf-fill"></i> Surat
                                                </button>
                                                <button v-if="!locks[4]?.is_locked && canWrite" class="btn btn-xs btn-light border px-2 py-1 rounded-lg text-[10px] font-semibold text-slate-600 hover:bg-slate-100"
                                                        title="Batalkan Pengunduran Diri" @click="cancelPengunduranDiri(stu)">
                                                    <i class="bi bi-arrow-counterclockwise"></i> Batal
                                                </button>
                                            </template>
                                            
                                            <!-- Eligible actions (Pengunduran Diri & Manual Non-Eligible) -->
                                            <template v-else-if="stu.is_eligible">
                                                <button v-if="!locks[4]?.is_locked && canWrite" class="btn btn-xs btn-outline-danger px-2 py-1 rounded-lg text-[10px] font-bold flex items-center gap-1 shadow-2xs"
                                                        title="Catat Pengunduran Diri Resmi & Pelimpahan Kuota"
                                                        @click="openModalPengunduranDiri(stu)">
                                                    <i class="bi bi-person-x-fill"></i> Mundur
                                                </button>
                                                <span v-else class="text-[10px] text-emerald-600 font-bold"><i class="bi bi-check2-circle"></i> Siap SNBP</span>
                                            </template>

                                            <!-- Manual BK overrides -->
                                            <div v-if="!locks[4]?.is_locked && canWrite && !stu.is_resigned" class="flex gap-1 ms-0.5">
                                                <!-- Jika Siswa Eligible: Berikan tombol ubah ke Tidak Eligible secara manual -->
                                                <button v-if="stu.is_eligible && stu.status_eligible !== 'tidak_eligible'" 
                                                        class="btn btn-xs btn-danger py-1 px-2 rounded-lg text-[9px] font-bold uppercase border-0 text-white shadow-2xs hover:bg-rose-700 transition-all" 
                                                        title="Ubah manual status kelayakan menjadi Tidak Eligible" 
                                                        @click="saveManualEligible(stu.id, 'tidak_eligible')">
                                                    Non-Elig
                                                </button>

                                                <!-- Jika Siswa Tidak Eligible: Berikan tombol ubah ke Eligible secara manual -->
                                                <button v-if="!stu.is_eligible && stu.status_eligible !== 'eligible'" 
                                                        class="btn btn-xs btn-success py-1 px-2 rounded-lg text-[9px] font-bold uppercase border-0 text-white shadow-2xs hover:bg-emerald-700 transition-all" 
                                                        title="Paksa status siswa menjadi Eligible (BK Manual)" 
                                                        @click="saveManualEligible(stu.id, 'eligible')">
                                                    + Elig
                                                </button>

                                                <!-- Tombol Reset ke Auto jika pernah di-override manual -->
                                                <button v-if="stu.status_eligible !== 'auto'" 
                                                        class="btn btn-xs btn-secondary py-1 px-2 rounded-lg text-[9px] font-bold uppercase border-0 text-white shadow-2xs hover:bg-slate-600 transition-all" 
                                                        title="Kembalikan status siswa ke kalkulasi otomatis kuota & nilai" 
                                                        @click="saveManualEligible(stu.id, 'auto')">
                                                    Reset
                                                </button>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </template>
    </div>

    <!-- TAB 2: TRACKING ALUMNI & REKAM KAMPUS (ALUMNI CAREER TRACKING) -->
    <div v-show="activeTab === 'tracking'" class="space-y-6">
        <div v-if="userRole === 'super_admin' && !currentTenantId" class="bg-amber-50 border border-amber-100 rounded-2xl p-8 text-center shadow-sm">
            <div class="w-16 h-16 rounded-full bg-amber-100 text-amber-600 flex items-center justify-center mx-auto mb-4">
                <i class="bi bi-funnel-fill text-2xl"></i>
            </div>
            <h4 class="font-bold text-slate-800 text-base">Pilih Sekolah Terlebih Dahulu</h4>
            <p class="text-slate-500 text-xs mt-1 max-w-sm mx-auto">Silakan pilih sekolah pada filter di bagian atas halaman untuk menampilkan data.</p>
        </div>
        <template v-else>
            <!-- Filter Toolbar -->
        <div class="bg-white rounded-2xl shadow-xs border border-slate-200/80 p-4 mb-4">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex flex-wrap gap-2 items-center">
                    <!-- Search Input -->
                    <div class="position-relative">
                        <i class="bi bi-search position-absolute text-slate-400 text-xs" style="left: 12px; top: 50%; transform: translateY(-50%);"></i>
                        <input type="text" v-model="filterAlumni.search" placeholder="Cari nama alumni..." class="rounded-xl border border-slate-300 pl-8 pr-3 py-1.5 text-xs text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500 w-48 shadow-2xs">
                    </div>
                    <!-- Type Filter -->
                    <select v-model="filterAlumni.type" class="rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-xs text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500 shadow-2xs">
                        <option value="">Semua Jenis Kampus</option>
                        <option value="Negeri">Negeri (PTN)</option>
                        <option value="Swasta">Swasta (PTS)</option>
                        <option value="Kedinasan">Kedinasan (PTK)</option>
                    </select>
                    <!-- Track Filter -->
                    <select v-model="filterAlumni.track" class="rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-xs text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500 shadow-2xs">
                        <option value="">Semua Jalur Masuk</option>
                        <option value="SNBP">SNBP</option>
                        <option value="SNBT">SNBT</option>
                        <option value="Mandiri">Mandiri</option>
                        <option value="Beasiswa">Beasiswa</option>
                        <option value="Jalur Swasta">Jalur Swasta</option>
                        <option value="Kedinasan">Kedinasan</option>
                        <option value="Lainnya">Lainnya</option>
                    </select>
                    <!-- Year Filter -->
                    <select v-model="filterAlumni.year" class="rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-xs text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500 shadow-2xs">
                        <option value="">Semua Tahun Masuk</option>
                        <option v-for="yr in uniqueAlumniYears" :key="yr" :value="yr">{{ yr }}</option>
                    </select>
                </div>

                <div class="flex items-center gap-3 flex-wrap">
                    <!-- Privacy Toggle -->
                    <div class="flex items-center gap-2 bg-slate-50 border border-slate-200/80 rounded-xl px-3 py-1.5 shadow-2xs">
                        <label for="privacy-mask" class="text-xs font-bold text-slate-600 cursor-pointer flex items-center gap-1.5 m-0 select-none">
                            <i class="bi bi-shield-shaded text-indigo-500"></i>
                            Sensor Nama Alumni
                        </label>
                        <input type="checkbox" id="privacy-mask" v-model="privacyMask" :disabled="isStudent" class="rounded text-blue-600 border-slate-300 focus:ring-blue-500 cursor-pointer">
                    </div>

                    <!-- Add Alumni Button -->
                    <button v-if="canWrite" class="btn btn-primary rounded-xl px-4 py-2 text-xs font-bold flex items-center gap-1.5 shadow-sm"
                            @click="openAlumniModal()">
                        <i class="bi bi-plus-lg"></i>
                        Tambah Alumni
                    </button>
                </div>
            </div>
        </div>

        <!-- Grid Data Grid Table -->
        <div class="bg-white rounded-2xl shadow-xs border border-slate-200/80 overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr class="bg-slate-50/80 border-b border-slate-200/80 text-[10px] text-slate-600 font-bold uppercase tracking-wider">
                            <th class="ps-6 py-3">Tahun Lulus/Masuk</th>
                            <th class="py-3">Nama Alumni</th>
                            <th class="py-3">Jenis Kampus</th>
                            <th class="py-3">Jalur Masuk</th>
                            <th class="py-3">Nama Kampus</th>
                            <th class="py-3">Program Studi</th>
                            <th class="py-3 text-center">Status</th>
                            <th v-if="canWrite" class="py-3 text-end pe-6">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Loading State -->
                        <tr v-if="loading">
                            <td colspan="8" class="text-center py-10 text-slate-400 text-xs">
                                <div class="spinner-border spinner-border-sm text-primary me-2"></div>
                                Memuat data tracking alumni...
                            </td>
                        </tr>
                        <!-- Empty State -->
                        <tr v-else-if="filteredAlumniData.length === 0">
                            <td colspan="8" class="text-center py-10 text-slate-400 text-xs">
                                Belum ada data tracking alumni yang terekam atau cocok dengan kriteria.
                            </td>
                        </tr>
                        <!-- Records -->
                        <tr v-else v-for="al in filteredAlumniData" :key="al.id" class="text-sm border-b border-slate-100 hover:bg-slate-50">
                            <td class="ps-6 py-2.5 font-semibold text-slate-700">Tahun Kuliah: {{ al.tahun_masuk }}</td>
                            <td class="font-bold text-slate-800">
                                {{ privacyMask ? maskName(al.nama_alumni) : al.nama_alumni }}
                            </td>
                            <td>
                                <span class="text-xs font-semibold px-2 py-0.5 rounded-lg"
                                      :class="{
                                          'bg-blue-50 text-blue-700 border border-blue-100': al.jenis_campus === 'Negeri',
                                          'bg-indigo-50 text-indigo-700 border border-indigo-100': al.jenis_campus === 'Swasta',
                                          'bg-amber-50 text-amber-700 border border-amber-100': al.jenis_campus === 'Kedinasan'
                                      }">
                                    {{ al.jenis_campus }}
                                </span>
                            </td>
                            <td>
                                <span class="badge rounded-pill bg-light text-slate-800 border px-3 py-1 font-semibold fs-8">
                                    {{ al.jalur_masuk }}
                                </span>
                            </td>
                            <td class="font-semibold text-slate-700">{{ al.universitas_nama }}</td>
                            <td class="text-slate-600">{{ al.jurusan_nama }}</td>
                            <td class="text-center">
                                <span class="text-xs px-2.5 py-1 rounded-full font-bold uppercase"
                                      :class="al.status === 'Lulus' ? 'bg-emerald-50 text-emerald-700 border border-emerald-100' : 'bg-blue-50 text-blue-700 border border-blue-100'">
                                    {{ al.status }}
                                </span>
                            </td>
                            <td v-if="canWrite" class="text-end pe-6">
                                <div class="d-flex justify-content-end gap-1.5">
                                    <button class="btn btn-sm btn-light border px-2 py-1 text-slate-600 hover:bg-slate-100 rounded-lg text-xs" @click="openAlumniModal(al)">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-sm btn-light border px-2 py-1 text-rose-600 hover:bg-rose-50 hover:text-rose-700 rounded-lg text-xs" @click="deleteAlumniTrack(al)">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
              <!-- MODAL ADD/EDIT ALUMNI (INLINE GLASSMORPHISM OVERLAY) -->
        <Teleport to="body">
        <div v-if="modalAlumni.show" class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
            <div class="bg-white border rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden animate-fade-in">
                <!-- Header -->
                <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                    <h3 class="font-bold text-slate-800 text-base mb-0 flex items-center gap-2">
                        <i class="bi" :class="modalAlumni.form.id ? 'bi-pencil-square text-indigo-500' : 'bi-plus-circle text-blue-500'"></i>
                        {{ modalAlumni.form.id ? 'Edit Data Alumni' : 'Tambah Data Alumni' }}
                    </h3>
                    <button class="text-slate-400 hover:text-slate-600 text-xl font-bold bg-transparent border-0" @click="modalAlumni.show = false">&times;</button>
                </div>
                <!-- Body -->
                <form @submit.prevent="saveAlumniTrack">
                    <div class="p-5 space-y-3.5 text-left">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div class="relative">
                                <label for="al_name" class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Nama Alumni <span class="text-rose-500">*</span></label>
                                <div class="relative">
                                    <input type="text" id="al_name" 
                                           v-model="modalAlumni.form.nama_alumni" 
                                           @input="searchStudents"
                                           @focus="showSearchDropdown = searchResults.length > 0"
                                           placeholder="Cari siswa atau ketik nama manual" 
                                           required 
                                           class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500 pe-8">
                                    <i class="bi bi-search absolute right-3 top-2.5 text-slate-400"></i>
                                </div>
                                
                                <!-- Hasil Pencarian Dropdown -->
                                <div v-if="showSearchDropdown && searchResults.length > 0" 
                                     class="absolute z-[9999] left-0 right-0 mt-1 bg-white border border-slate-200 rounded-xl shadow-lg max-h-48 overflow-y-auto">
                                    <ul class="m-0 p-1 list-none">
                                        <li v-for="s in searchResults" :key="s.id" 
                                            @click="selectStudent(s)"
                                            class="px-3 py-2 hover:bg-slate-50 cursor-pointer rounded-lg border-b border-slate-50 last:border-0">
                                            <div class="font-bold text-xs text-slate-800">{{ s.nama_lengkap }}</div>
                                            <div class="text-[10px] text-slate-500">NISN: {{ s.nisn || '-' }} | NIS: {{ s.nis || '-' }}</div>
                                        </li>
                                    </ul>
                                </div>
                                <div v-else-if="searchingStudents" class="absolute z-[9999] left-0 right-0 mt-1 bg-white border border-slate-200 rounded-xl shadow-lg p-3 text-center text-xs text-slate-400">
                                    <div class="spinner-border spinner-border-sm text-primary me-2"></div>Mencari...
                                </div>
                                <div v-else-if="showSearchDropdown && searchResults.length === 0 && modalAlumni.form.nama_alumni.length >= 2" 
                                     class="absolute z-[9999] left-0 right-0 mt-1 bg-white border border-slate-200 rounded-xl shadow-lg p-3 text-center text-xs text-slate-400">
                                    <i class="bi bi-info-circle me-1"></i> Tidak ada siswa cocok. Tekan Enter untuk simpan nama manual.
                                </div>

                                <!-- Konfirmasi Terpilih -->
                                <div v-if="modalAlumni.selectedStudent" class="mt-2 p-2 bg-emerald-50 border border-emerald-200 rounded-lg flex items-center gap-2 animate-fade-in">
                                    <i class="bi bi-check-circle-fill text-emerald-500 fs-6"></i>
                                    <div class="text-[11px] leading-tight">
                                        <span class="block font-bold text-emerald-800">{{ modalAlumni.selectedStudent.nama_lengkap }}</span>
                                        <span class="block text-emerald-600">NISN: {{ modalAlumni.selectedStudent.nisn || '-' }} | NIS: {{ modalAlumni.selectedStudent.nis || '-' }}</span>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <label for="al_year" class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Tahun Masuk Kuliah <span class="text-rose-500">*</span></label>
                                <input type="number" id="al_year" v-model.number="modalAlumni.form.tahun_masuk" required min="1900" max="2050" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div>
                                <label for="al_type" class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Jenis Perguruan Tinggi <span class="text-rose-500">*</span></label>
                                <select id="al_type" v-model="modalAlumni.form.jenis_kampus" required class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="Negeri">Negeri</option>
                                    <option value="Swasta">Swasta</option>
                                    <option value="Kedinasan">Kedinasan</option>
                                </select>
                            </div>
                            <div>
                                <label for="al_track" class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Jalur Masuk Kuliah <span class="text-rose-500">*</span></label>
                                <select id="al_track" v-model="modalAlumni.form.jalur_masuk" required class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="SNBP">SNBP</option>
                                    <option value="SNBT">SNBT</option>
                                    <option value="Mandiri">Mandiri</option>
                                    <option value="Beasiswa">Beasiswa</option>
                                    <option value="Jalur Swasta">Jalur Swasta</option>
                                    <option value="Kedinasan">Kedinasan</option>
                                    <option value="Lainnya">Lainnya</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label for="al_uni" class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Nama Kampus <span class="text-rose-500">*</span></label>
                            <input type="text" id="al_uni" v-model="modalAlumni.form.universitas_nama" placeholder="e.g. Universitas Indonesia (UI)" required class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div>
                                <label for="al_major" class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Nama Jurusan / Program Studi <span class="text-rose-500">*</span></label>
                                <input type="text" id="al_major" v-model="modalAlumni.form.jurusan_nama" placeholder="e.g. Teknik Informatika" required class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label for="al_status" class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Status Kelulusan <span class="text-rose-500">*</span></label>
                                <select id="al_status" v-model="modalAlumni.form.status" required class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="Aktif">Aktif Kuliah</option>
                                    <option value="Lulus">Lulus</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <!-- Footer -->
                    <div class="px-5 py-4 border-t border-slate-100 flex items-center justify-end gap-2 bg-slate-50">
                        <button type="button" class="btn btn-light border rounded-xl px-4 py-2 text-xs font-semibold text-slate-600" @click="modalAlumni.show = false">Batal</button>
                        <button type="submit" class="btn btn-primary rounded-xl px-4 py-2 text-xs font-semibold flex items-center gap-1">
                            <i class="bi bi-floppy"></i>
                            Simpan Data
                        </button>
                    </div>
                </form>
            </div>
        </div>
        </Teleport>      </div>
        </template>
    </div>

    <!-- TAB 3: KONFIGURASI TARGET KAMPUS -->
    <!-- TAB: MASTER KAMPUS & JALUR -->
    <template v-if="activeTab === 'master_kampus' || activeTab === 'master_jalur'">
        <?php include __DIR__ . '/../bk/kampus_config_ui.php'; ?>
    </template>

    <!-- TAB: SIMULASI PEMILIHAN KAMPUS & PRODI -->
    <template v-if="activeTab === 'simulasi'">
        <?php include __DIR__ . '/../bk/pdss_simulasi_ui.php'; ?>
    </template>

    <!-- MODAL AUDIT DETAIL NILAI RAPOR -->
    <Teleport to="body">
        <!-- MODAL AUDIT NILAI SISWA (5-6 SEMESTER TRANSKRIP LENGKAP - FULLY SCROLLABLE) -->
        <div class="modal fade" id="modalAuditGrades" tabindex="-1" aria-hidden="true" style="z-index: 1060;">
            <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content border-0 rounded-2xl shadow-2xl overflow-hidden" style="max-height: 88vh;">
                    <!-- Header -->
                    <div class="modal-header border-b border-slate-200 px-6 py-4 flex items-center justify-between bg-slate-50 flex-shrink-0">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-indigo-600 text-white flex items-center justify-center shadow-xs">
                                <i class="bi bi-file-earmark-spreadsheet-fill text-base"></i>
                            </div>
                            <div>
                                <h5 class="modal-title font-bold text-sm text-slate-800">Transkrip & Audit Nilai Rapor Siswa</h5>
                                <p class="text-xs text-slate-500 mb-0">Rincian nilai per mata pelajaran Semester 1 s.d. 5/6 untuk evaluasi PDSS & SNBP</p>
                            </div>
                        </div>
                        <button type="button" class="btn-close text-slate-400 bg-transparent border-0 text-xl font-bold" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    
                    <!-- Body -->
                    <div class="modal-body p-6 overflow-y-auto" v-if="auditGrades && auditGrades.student">
                        <!-- Student Info Card -->
                        <div class="bg-gradient-to-r from-blue-50/70 via-indigo-50/50 to-purple-50/70 border border-indigo-100 rounded-2xl p-4 mb-4 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 flex-shrink-0">
                            <div>
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Nama Lengkap</span>
                                <span class="text-xs font-bold text-slate-900 block mt-0.5">{{ auditGrades.student.nama_lengkap }}</span>
                            </div>
                            <div>
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">NISN / NIS</span>
                                <span class="text-xs font-semibold text-slate-700 block mt-0.5 font-mono">{{ auditGrades.student.nisn || auditGrades.student.nis || '—' }}</span>
                            </div>
                            <div>
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Kelas & Jurusan</span>
                                <span class="text-xs font-semibold text-slate-700 block mt-0.5">{{ auditGrades.student.nama_kelas || '—' }} ({{ auditGrades.student.nama_jurusan || 'Umum' }})</span>
                            </div>
                            <div>
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Total Komponen Nilai</span>
                                <span class="text-xs font-bold text-indigo-700 block mt-0.5">{{ auditGrades.total_mapel_diambil }} Nilai Terisi</span>
                            </div>
                            <div>
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Rata-rata Akumulatif SNBP</span>
                                <span class="text-sm font-extrabold text-emerald-600 block mt-0.5">{{ auditGrades.rata_rata ? auditGrades.rata_rata.toFixed(2) : '—' }}</span>
                            </div>
                        </div>

                        <!-- Audit Table (Scrollable with Sticky Headers) -->
                        <div class="table-responsive rounded-2xl border border-slate-200 shadow-2xs overflow-auto" style="max-height: 50vh;">
                            <table class="table table-bordered align-middle text-xs mb-0 w-full" style="min-width: 780px;">
                                <thead class="bg-slate-100 text-center font-bold text-slate-700 sticky top-0 z-10 shadow-2xs">
                                    <tr class="border-b border-slate-200">
                                        <th rowspan="2" class="align-middle text-start ps-4 py-2.5 bg-slate-100" style="min-width: 200px;">Nama Mata Pelajaran</th>
                                        <th rowspan="2" class="align-middle text-start ps-3 py-2.5 bg-slate-100" style="min-width: 130px;">Kategori</th>
                                        <th colspan="2" class="py-2 bg-blue-50 text-blue-900 border-l border-blue-200">Kelas X (Fase E)</th>
                                        <th colspan="2" class="py-2 bg-indigo-50 text-indigo-900 border-l border-indigo-200">Kelas XI (Fase F)</th>
                                        <th colspan="2" class="py-2 bg-purple-50 text-purple-900 border-l border-purple-200">Kelas XII (Fase F)</th>
                                        <th rowspan="2" class="align-middle text-center py-2.5 bg-slate-100 border-l" style="width: 90px;">Rata-rata Mapel</th>
                                    </tr>
                                    <tr class="bg-slate-50 text-[10px] font-semibold text-slate-600">
                                        <th class="py-1 border-l border-slate-200" style="width: 65px;">Sem 1</th>
                                        <th class="py-1" style="width: 65px;">Sem 2</th>
                                        <th class="py-1 border-l border-slate-200" style="width: 65px;">Sem 3</th>
                                        <th class="py-1" style="width: 65px;">Sem 4</th>
                                        <th class="py-1 border-l border-slate-200" style="width: 65px;">Sem 5</th>
                                        <th class="py-1" style="width: 65px;">Sem 6</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="g in auditGrades.grades" :key="g.mapel_id" 
                                        :class="{
                                            'bg-indigo-50/70 border-y-2 border-indigo-200 font-bold': g.is_ma_aggregated,
                                            'bg-amber-50/20': g.is_ma_sub,
                                            'hover:bg-slate-50/80': !g.is_ma_aggregated
                                        }"
                                        class="transition-colors">
                                        <td class="py-2" :class="g.is_ma_sub ? 'ps-8' : 'ps-4'">
                                            <div class="flex items-center gap-1.5" v-if="g.is_ma_aggregated">
                                                <i class="bi bi-layers-fill text-indigo-600 text-sm"></i>
                                                <span class="font-extrabold text-indigo-950">{{ g.nama_mapel }}</span>
                                            </div>
                                            <div class="flex items-center gap-1.5" v-else-if="g.is_ma_sub">
                                                <span class="text-slate-400 font-mono text-xs">├──</span>
                                                <i class="bi bi-book-half text-amber-600 text-xs"></i>
                                                <span class="font-medium text-slate-700">{{ g.nama_mapel }}</span>
                                            </div>
                                            <div class="flex items-center gap-1.5" v-else>
                                                <i class="bi bi-journal-text text-slate-400 text-xs"></i>
                                                <span class="font-semibold text-slate-800">{{ g.nama_mapel }}</span>
                                            </div>
                                        </td>
                                        <td class="ps-3">
                                            <span v-if="g.is_ma_aggregated" class="badge bg-indigo-600 text-white font-bold text-[9px] px-2 py-0.5 rounded-md shadow-2xs">
                                                Rumpun Resmi PDSS
                                            </span>
                                            <span v-else-if="g.is_ma_sub" class="badge bg-amber-100 text-amber-800 border border-amber-200 font-semibold text-[9px] px-2 py-0.5 rounded-md">
                                                Sub-Mapel MA
                                            </span>
                                            <span v-else class="badge rounded-md font-semibold text-[9px] px-2 py-0.5"
                                                  :class="getMapelCategoryBadge(g.kode_mapel)">
                                                {{ g.kode_mapel || 'Wajib' }}
                                            </span>
                                        </td>
                                        <!-- Sem 1-6 -->
                                        <td v-for="sem in [1,2,3,4,5,6]" :key="sem" class="text-center py-2" :class="{'border-l': sem % 2 === 1}">
                                            <div v-if="g['sem_' + sem].is_configured">
                                                <span :class="g.is_ma_aggregated ? 'font-extrabold text-indigo-900 text-xs' : 'font-bold text-slate-900'" v-if="g['sem_' + sem].nilai !== null">
                                                    {{ g['sem_' + sem].nilai }}
                                                </span>
                                                <span class="text-slate-300 font-normal" v-else>—</span>
                                                <span class="text-[8px] text-slate-400 block font-normal leading-tight" v-if="g['sem_' + sem].tahun_ajaran && !g.is_ma_aggregated">
                                                    {{ g['sem_' + sem].tahun_ajaran }}
                                                </span>
                                            </div>
                                            <div v-else class="text-[9px] text-slate-300 py-0.5 select-none">
                                                —
                                            </div>
                                        </td>
                                        <!-- Rata-rata Mapel -->
                                        <td class="text-center py-2 border-l" :class="g.is_ma_aggregated ? 'font-extrabold text-indigo-900 bg-indigo-100/50' : 'font-bold text-indigo-700 bg-slate-50/50'">
                                            {{ g.rata_rata ? g.rata_rata.toFixed(2) : '—' }}
                                        </td>
                                    </tr>
                                </tbody>
                                <!-- Footer Rata-rata Semester (Sticky Bottom) -->
                                <tfoot class="bg-slate-100 font-bold text-slate-800 border-t-2 border-slate-300 sticky bottom-0 z-10 shadow-2xs">
                                    <tr>
                                        <td colspan="2" class="ps-4 py-2.5 text-start text-xs font-extrabold uppercase text-slate-700 bg-slate-100">
                                            <i class="bi bi-calculator-fill text-blue-600 me-1"></i> Rata-rata Semester:
                                        </td>
                                        <td class="text-center py-2.5 border-l text-blue-800 bg-slate-100">
                                            {{ auditGrades.semester_avgs && auditGrades.semester_avgs[1] ? auditGrades.semester_avgs[1].toFixed(2) : '—' }}
                                        </td>
                                        <td class="text-center py-2.5 text-blue-800 bg-slate-100">
                                            {{ auditGrades.semester_avgs && auditGrades.semester_avgs[2] ? auditGrades.semester_avgs[2].toFixed(2) : '—' }}
                                        </td>
                                        <td class="text-center py-2.5 border-l text-indigo-800 bg-slate-100">
                                            {{ auditGrades.semester_avgs && auditGrades.semester_avgs[3] ? auditGrades.semester_avgs[3].toFixed(2) : '—' }}
                                        </td>
                                        <td class="text-center py-2.5 text-indigo-800 bg-slate-100">
                                            {{ auditGrades.semester_avgs && auditGrades.semester_avgs[4] ? auditGrades.semester_avgs[4].toFixed(2) : '—' }}
                                        </td>
                                        <td class="text-center py-2.5 border-l text-purple-800 bg-slate-100">
                                            {{ auditGrades.semester_avgs && auditGrades.semester_avgs[5] ? auditGrades.semester_avgs[5].toFixed(2) : '—' }}
                                        </td>
                                        <td class="text-center py-2.5 text-purple-800 bg-slate-100">
                                            {{ auditGrades.semester_avgs && auditGrades.semester_avgs[6] ? auditGrades.semester_avgs[6].toFixed(2) : '—' }}
                                        </td>
                                        <td class="text-center py-2.5 border-l bg-emerald-100 text-emerald-900 text-sm font-extrabold">
                                            {{ auditGrades.rata_rata ? auditGrades.rata_rata.toFixed(2) : '—' }}
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                    <div class="modal-body p-6 text-center text-slate-400 text-xs py-14" v-else>
                        <div class="spinner-border spinner-border-sm text-primary me-2"></div>
                        Memuat data transkrip nilai rapor siswa...
                    </div>
                    <!-- Footer Action -->
                    <div class="modal-footer border-t border-slate-200 px-6 py-3 bg-slate-50 flex items-center justify-between flex-shrink-0">
                        <div class="text-[11px] text-slate-500 font-medium">
                            <i class="bi bi-shield-check text-emerald-600"></i> Nilai terverifikasi dari Buku Induk Rapor Siswa
                        </div>
                        <button type="button" class="btn btn-sm btn-secondary rounded-xl px-4 py-1.5 text-xs font-semibold" data-bs-dismiss="modal">
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODAL FORM SURAT PENGUNDURAN DIRI SISWA -->
        <div v-if="modalPengunduranDiri.show" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs" style="position: fixed; inset: 0; z-index: 1070; display: flex; align-items: center; justify-content: center; background: rgba(15, 23, 42, 0.65);">
            <div class="bg-white rounded-3xl shadow-2xl w-full max-w-lg overflow-hidden border border-slate-100 animate-scale-in">
                <!-- Header -->
                <div class="px-6 py-4 bg-gradient-to-r from-rose-50 to-orange-50 border-b border-rose-100 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-rose-100 text-rose-600 flex items-center justify-center shadow-xs">
                            <i class="bi bi-file-earmark-person-fill text-lg"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-sm text-slate-800">Surat Pengunduran Diri SNBP</h4>
                            <p class="text-[11px] text-slate-500 mb-0">Upload berkas pernyataan resmi bertanda tangan siswa & ortu</p>
                        </div>
                    </div>
                    <button type="button" class="btn-close text-slate-400" @click="modalPengunduranDiri.show = false"></button>
                </div>

                <!-- Form -->
                <form @submit.prevent="submitPengunduranDiri" class="p-6 space-y-4">
                    <div v-if="modalPengunduranDiri.siswa" class="bg-slate-50 p-3 rounded-2xl border border-slate-100 text-xs space-y-1">
                        <div class="font-bold text-slate-800">{{ modalPengunduranDiri.siswa.nama_lengkap }}</div>
                        <div class="text-slate-500">NISN: {{ modalPengunduranDiri.siswa.nisn || '—' }} · Kelas: {{ modalPengunduranDiri.siswa.nama_kelas || '—' }} · Jurusan: {{ modalPengunduranDiri.siswa.nama_jurusan || '—' }}</div>
                        <div class="text-indigo-600 font-semibold">Peringkat Jurusan Saat Ini: #{{ modalPengunduranDiri.siswa.majorRank }}</div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Nomor Surat Pernyataan</label>
                            <input type="text" v-model="modalPengunduranDiri.nomor_surat" placeholder="e.g. 421/BK/PDSS/2026" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs focus:ring-2 focus:ring-rose-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Tanggal Surat <span class="text-rose-500">*</span></label>
                            <input type="date" v-model="modalPengunduranDiri.tanggal_surat" required class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs focus:ring-2 focus:ring-rose-500 focus:outline-none">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Alasan Pengunduran Diri <span class="text-rose-500">*</span></label>
                        <textarea v-model="modalPengunduranDiri.alasan" required rows="2" placeholder="e.g. Memilih fokus persiapan Kedinasan / Kuliah Luar Negeri / Bekerja..." class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs focus:ring-2 focus:ring-rose-500 focus:outline-none"></textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Unggah Scan Surat Pernyataan (PDF/JPG/PNG) <span class="text-rose-500">*</span></label>
                        <input type="file" ref="suratPengunduranFileInput" @change="handleSuratFileChange" accept=".pdf,.jpg,.jpeg,.png,.webp" required class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs file:mr-3 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-rose-50 file:text-rose-700 hover:file:bg-rose-100">
                        <span class="text-[10px] text-slate-400 block mt-1">Maksimal 10MB. Format PDF, JPG, PNG, WEBP.</span>
                    </div>

                    <div class="bg-amber-50 border border-amber-200 p-3 rounded-2xl text-[11px] text-amber-800 flex items-start gap-2">
                        <i class="bi bi-info-circle-fill text-amber-600 flex-shrink-0 mt-0.5"></i>
                        <div>
                            <strong>Catatan Sistem:</strong> Setelah disimpan, status siswa ini akan diubah menjadi <em>Mengundurkan Diri</em> dan kuota eligible akan <strong>otomatis dilimpahkan ke siswa peringkat di bawahnya</strong>.
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                        <button type="button" class="btn btn-sm btn-light border rounded-xl px-4 py-2 text-xs font-semibold" @click="modalPengunduranDiri.show = false">Batal</button>
                        <button type="submit" class="btn btn-sm btn-danger rounded-xl px-4 py-2 text-xs font-semibold flex items-center gap-1.5 shadow-sm" :disabled="modalPengunduranDiri.saving">
                            <span v-if="modalPengunduranDiri.saving" class="spinner-border spinner-border-sm me-1"></span>
                            <i class="bi bi-check-circle-fill"></i> Simpan & Limpahkan Kuota
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL DETAIL LIHAT SURAT PENGUNDURAN DIRI -->
        <div v-if="modalDetailSurat.show" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs" style="position: fixed; inset: 0; z-index: 1070; display: flex; align-items: center; justify-content: center; background: rgba(15, 23, 42, 0.65);">
            <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md overflow-hidden border border-slate-100 animate-scale-in">
                <div class="px-6 py-4 bg-slate-50 border-b border-slate-100 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <i class="bi bi-file-earmark-check-fill text-rose-600 text-lg"></i>
                        <h5 class="font-bold text-sm text-slate-800 mb-0">Berkas Pengunduran Diri</h5>
                    </div>
                    <button type="button" class="btn-close text-slate-400" @click="modalDetailSurat.show = false"></button>
                </div>
                <div class="p-6 space-y-4" v-if="modalDetailSurat.data">
                    <div class="bg-slate-50 p-4 rounded-2xl border border-slate-100 space-y-2 text-xs">
                        <div>
                            <span class="text-slate-400 block text-[10px] uppercase font-bold">Nama Siswa</span>
                            <span class="font-bold text-slate-800">{{ modalDetailSurat.data.nama_lengkap }}</span>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <span class="text-slate-400 block text-[10px] uppercase font-bold">Nomor Surat</span>
                                <span class="font-semibold text-slate-700">{{ modalDetailSurat.data.nomor_surat || '—' }}</span>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[10px] uppercase font-bold">Tanggal Surat</span>
                                <span class="font-semibold text-slate-700">{{ modalDetailSurat.data.tanggal_surat || '—' }}</span>
                            </div>
                        </div>
                        <div>
                            <span class="text-slate-400 block text-[10px] uppercase font-bold">Alasan Pengunduran Diri</span>
                            <p class="text-slate-600 mb-0 italic">"{{ modalDetailSurat.data.alasan || 'Tidak ada catatan alasan.' }}"</p>
                        </div>
                    </div>

                    <div class="text-center pt-2">
                        <a :href="baseUrl + '/' + modalDetailSurat.data.path_file" target="_blank" class="btn btn-sm btn-primary rounded-xl px-4 py-2 text-xs font-semibold inline-flex items-center gap-2 shadow-sm">
                            <i class="bi bi-file-earmark-arrow-down-fill"></i> Buka / Unduh File Berkas
                        </a>
                    </div>
                </div>
                <div class="p-6 text-center text-slate-400 text-xs" v-else>
                    <div class="spinner-border spinner-border-sm text-primary me-2"></div>
                    Memuat rincian berkas...
                </div>
            </div>
        </div>
    </Teleport>

</div>

<!-- Vue 3 Interactive Routing & Lifecycle Scripts -->
<script>
{
    const _baseUrl = '<?= rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') ?>';
    const _userRole = '';
    const _canWrite = true;
    const _currentTenantId = null;

    window.VueAppRegistry.register('#pdssApp', {
        data() {
            return {
                baseUrl: _baseUrl,
                currentTenantId: _currentTenantId,
                userRole: _userRole,
                canWrite: _canWrite,
                activeTab: '<?= $allowed_pdss_tabs[0] ?? "kesiapan" ?>',
                accreditation: 'A',
                students: [],
                alumniData: [],
                campusData: [],
                activeConfigTab: 'kampus',
                listKampus: [],
                listProdi: [],
                listRiwayat: [],
                listJalur: [],
                loadingKampus: false,
                importingExcel: false,
                modalImportExcel: { show: false },

                loadingProdi: false,
                loadingJalur: false,
                
                modalMstKampus: {
                    show: false,
                    form: { id: '', nama_kampus: '', kota_kampus: '', alamat_kampus: '', jenis_kampus: 'Negeri' }
                },
                modalProdi: {
                    show: false,
                    kampus: null,
                    expandedProdiId: null,
                    form: { id: '', kampus_id: '', fakultas: '', program_studi: '', jenjang: 'S1' }
                },
                formRiwayat: { prodi_id: '', tahun: new Date().getFullYear(), daya_tampung: 0, jumlah_pendaftar: 0 },
                modalMstJalur: {
                    show: false,
                    form: { id: '', nama_jalur: '', kategori: 'Lainnya' }
                },

                loading: false,
                quotaPercent: 40,
                searchStudent: '',
                filterMajor: '',
                filterClass: '',
                searchMapelConfig: '',
                masterMajors: [],
                filterMapelMajor: '',
                filterMapelCategory: '',
                mapelCategories: [],
                pdssMapels: [],
                loadingMapels: false,
                savingMapels: false,
                showMapelConfig: true,
                mapelNotConfigured: false,
                totalConfiguredMapels: 0,
                totalConfiguredSemesters: 0,
                downloadSemester: 1,
                filterAcademicYear: '',
                academicYears: [],
                useERapor: false,
                locks: {
                    1: { is_locked: false, locked_by: null, locked_at: null },
                    2: { is_locked: false, locked_by: null, locked_at: null },
                    3: { is_locked: false, locked_by: null, locked_at: null },
                    4: { is_locked: false, locked_by: null, locked_at: null }
                },
                modalPengunduranDiri: {
                    show: false,
                    siswa: null,
                    nomor_surat: '',
                    tanggal_surat: new Date().toISOString().slice(0, 10),
                    alasan: '',
                    file: null,
                    saving: false
                },
                modalDetailSurat: {
                    show: false,
                    data: null,
                    loading: false
                },
                auditGrades: null,
                privacyMask: true,
                isStudent: _userRole === 'siswa',
                canWrite: _canWrite,
                showSearchDropdown: false,
                searchResults: [],
                searchingStudents: false,

                // Filter Alumni
                filterAlumni: {
                    search: '',
                    type: '',
                    track: '',
                    year: ''
                },

                // Alumni Form Modal state
                modalAlumni: {
                    show: false,
                    form: {
                        id: '',
                        id_siswa: '',
                        nama_alumni: '',
                        tahun_masuk: new Date().getFullYear(),
                        jenis_kampus: 'Negeri',
                        jalur_masuk: 'SNBP',
                        universitas_nama: '',
                        jurusan_nama: '',
                        status: 'Aktif'
                    },
                    selectedStudent: null
                },

                // Campus Form Modal state
                modalCampus: {
                    show: false,
                    form: {
                        id: '',
                        nama_kampus: '',
                        jenis_kampus: 'Negeri',
                        kuota_target: 5
                    }
                },

                // ============================================================
                // SIMULASI PEMILIHAN KAMPUS & PRODI
                // ============================================================
                activeNoSimulasi: 1,          // Simulasi aktif: 1, 2, atau 3
                simulasiData: [],             // Daftar siswa + pilihan + peringkat
                simulasiStats: { total_eligible: 0, sudah_isi: 0, belum_isi: 0, total_konflik: 0 },
                simulasiSettings: {
                    1: { is_open: 0, is_locked: 0 },
                    2: { is_open: 0, is_locked: 0 },
                    3: { is_open: 0, is_locked: 0 }
                },
                loadingSimulasi: false,
                filterSimulasi: {
                    search: '',
                    jurusan_id: '',
                    status_konflik: '',   // '' | 'konflik' | 'aman'
                    sudah_isi: ''         // '' | 'sudah' | 'belum'
                },
                listKampusFlat: [],           // Daftar kampus untuk dropdown pilihan
                listProdiByKampus: {},        // { kampus_id: [prodi, ...] }
                modalSimulasi: {
                    show: false,
                    siswa: null,
                    saving: false,
                    conflictMsg: '',
                    searchKampus1: '',
                    searchKampus2: '',
                    showDropdown1: false,
                    showDropdown2: false,
                    form: {
                        kampus_id_1: '', prodi_id_1: '',
                        kampus_id_2: '', prodi_id_2: '',
                        catatan_siswa: ''
                    }
                },
                modalUploadBukti: {
                    show: false,
                    siswa: null,
                    uploading: false,
                    file: null
                },
                modalDetailKonflik: {
                    show: false,
                    siswa: {},
                    slot: 1,
                    kampusNama: '',
                    prodiNama: '',
                    conflicts: []
                },
                recalculating: false
            };
        },

        watch: {
            useERapor(newVal) {
                if (!this.locks[2].is_locked) {
                    let base = 5;
                    if (this.accreditation.includes('A')) base = 40;
                    else if (this.accreditation.includes('B')) base = 25;
                    
                    this.quotaPercent = newVal ? (base + 5) : base;
                }
            },
            activeTab(newVal) {
                if (newVal === 'kesiapan') {
                    this.fetchKesiapan();
                    this.fetchPdssMapels();
                } else if (newVal === 'master_kampus') {
                    this.fetchKampus();
                } else if (newVal === 'master_jalur') {
                    this.fetchJalur();
                } else if (newVal === 'simulasi') {
                    this.fetchSimulasiSettings();
                    this.fetchSimulasi();
                    if (this.listKampusFlat.length === 0) this.fetchKampusFlatList();
                }
            }
        },

        computed: {
            totalConfiguredMapels() {
                if (!this.pdssMapels) return 0;
                return this.pdssMapels.filter(m => m.sem_1 || m.sem_2 || m.sem_3 || m.sem_4 || m.sem_5 || m.sem_6).length;
            },

            filteredPdssMapels() {
                if (!this.pdssMapels) return [];
                let list = this.pdssMapels;
                if (this.filterMapelCategory) {
                    const cat = this.filterMapelCategory.toLowerCase().trim();
                    list = list.filter(m => (m.kategori || '').toLowerCase().trim() === cat);
                }
                if (this.searchMapelConfig) {
                    const q = this.searchMapelConfig.toLowerCase().trim();
                    list = list.filter(m => 
                        (m.nama_mapel && m.nama_mapel.toLowerCase().includes(q)) ||
                        (m.kategori && m.kategori.toLowerCase().includes(q))
                    );
                }
                return list;
            },

            // Unik list untuk filter
            uniqueMajors() {
                const set = new Set();
                this.students.forEach(s => {
                    if (s.nama_jurusan) set.add(s.nama_jurusan);
                });
                return Array.from(set).sort();
            },

            uniqueClasses() {
                const set = new Set();
                this.students.forEach(s => {
                    if (s.nama_kelas) set.add(s.nama_kelas);
                });
                return Array.from(set).sort();
            },

            uniqueAlumniYears() {
                const set = new Set();
                this.alumniData.forEach(a => {
                    if (a.tahun_masuk) set.add(a.tahun_masuk);
                });
                return Array.from(set).sort((a,b) => b - a);
            },

            // SIMULASI RANKING PARALEL (Tab 1)
            // Dijalankan secara reaktif & instan di sisi client saat persentase kuota diubah
            processedStudents() {
                // 1. Kelompokkan siswa berdasarkan nama jurusan
                const groups = {};
                this.students.forEach(s => {
                    const jurKey = s.nama_jurusan || 'Umum';
                    if (!groups[jurKey]) {
                        groups[jurKey] = [];
                    }
                    groups[jurKey].push({...s});
                });

                const allProcessed = [];

                // 2. Beri ranking paralel dalam internal masing-masing jurusan & kalkulasi kelayakan kuota
                Object.keys(groups).forEach(jurKey => {
                    const group = groups[jurKey];
                    // Urutkan berdasarkan rata_rata nilai DESC, nama ASC
                    group.sort((a, b) => {
                        const scoreA = parseFloat(a.rata_rata || a.nilai_rata_rata || 0);
                        const scoreB = parseFloat(b.rata_rata || b.nilai_rata_rata || 0);
                        if (scoreB !== scoreA) {
                            return scoreB - scoreA;
                        }
                        return (a.nama_lengkap || '').localeCompare(b.nama_lengkap || '');
                    });

                    const N = group.length;
                    // Rumus kuota paralel SNBP nasional: batas = ceil(N * Quota / 100)
                    const limit = Math.max(1, Math.ceil(N * (this.quotaPercent || 40) / 100));
                    let activeEligibleCount = 0;

                    group.forEach((stu, index) => {
                        stu.majorRank = index + 1;
                        stu.ranking_jurusan = index + 1;
                        const score = parseFloat(stu.rata_rata || stu.nilai_rata_rata || 0);

                        if (stu.is_resigned) {
                            stu.is_eligible = false;
                            stu.isEligible = false;
                            stu.status_keterangan = 'Mengundurkan Diri';
                        } else if (stu.status_eligible === 'eligible') {
                            stu.is_eligible = true;
                            stu.isEligible = true;
                            stu.status_keterangan = 'Eligible (Manual)';
                            activeEligibleCount++;
                        } else if (stu.status_eligible === 'tidak_eligible') {
                            stu.is_eligible = false;
                            stu.isEligible = false;
                            stu.status_keterangan = 'Tidak Eligible (Manual)';
                        } else {
                            if (activeEligibleCount < limit && score > 0) {
                                stu.is_eligible = true;
                                stu.isEligible = true;
                                activeEligibleCount++;
                                stu.is_replacement = (index + 1 > limit);
                                stu.status_keterangan = stu.is_replacement ? 'Eligible (Pelimpahan Kuota)' : 'Eligible (Kuota Standar)';
                            } else {
                                stu.is_eligible = false;
                                stu.isEligible = false;
                                stu.status_keterangan = 'Di Luar Kuota';
                            }
                        }
                        allProcessed.push(stu);
                    });
                });

                // Urutkan kembali berdasarkan nama jurusan & rank internal untuk tampilan
                allProcessed.sort((a, b) => {
                    const jurA = a.nama_jurusan || '';
                    const jurB = b.nama_jurusan || '';
                    if (jurA !== jurB) {
                        return jurA.localeCompare(jurB);
                    }
                    return (a.majorRank || 0) - (b.majorRank || 0);
                });

                return allProcessed;
            },

            filteredKampus1() {
                const search = (this.modalSimulasi.searchKampus1 || '').toLowerCase().trim();
                if (!search) return this.listKampusFlat;
                return this.listKampusFlat.filter(c => c.nama_kampus.toLowerCase().includes(search));
            },

            filteredKampus2() {
                const search = (this.modalSimulasi.searchKampus2 || '').toLowerCase().trim();
                if (!search) return this.listKampusFlat;
                return this.listKampusFlat.filter(c => c.nama_kampus.toLowerCase().includes(search));
            },

            // Filtered Students List (Simulasi)
            filteredStudents() {
                let list = this.processedStudents;
                if (this.searchStudent.trim()) {
                    const q = this.searchStudent.toLowerCase();
                    list = list.filter(s => s.nama_lengkap.toLowerCase().includes(q) || (s.nisn && s.nisn.includes(q)) || (s.nis && s.nis.includes(q)));
                }
                if (this.filterMajor) {
                    list = list.filter(s => s.nama_jurusan === this.filterMajor);
                }
                if (this.filterClass) {
                    list = list.filter(s => s.nama_kelas === this.filterClass);
                }
                return list;
            },

            // Cohort Stats
            stats() {
                const list = this.filteredStudents;
                const totalStudents = list.length;
                let completenessRate = 0;
                let studentsWithGrades = 0;
                
                if (totalStudents > 0 && this.totalConfiguredSemesters > 0) {
                    const totalExpectedGrades = totalStudents * this.totalConfiguredSemesters;
                    const totalActualGrades = list.reduce((sum, s) => sum + s.jumlah_nilai, 0);
                    completenessRate = Math.min(100, Math.round((totalActualGrades / totalExpectedGrades) * 100));
                    studentsWithGrades = list.filter(s => s.jumlah_nilai > 0).length;
                } else if (totalStudents > 0) {
                    studentsWithGrades = list.filter(s => s.rata_rata > 0).length;
                    completenessRate = Math.round((studentsWithGrades / totalStudents) * 100);
                }
                
                // Hitung total eligible dari simulasi saat ini
                const eligibleCount = list.filter(s => s.is_eligible || s.isEligible).length;

                return {
                    totalStudents,
                    studentsWithGrades,
                    completenessRate,
                    eligibleCount
                };
            },

            // Filtered Alumni List (Tab 2)
            filteredAlumniData() {
                let list = this.alumniData;
                const q = this.filterAlumni.search.trim().toLowerCase();
                if (q) {
                    list = list.filter(a => a.nama_alumni.toLowerCase().includes(q));
                }
                if (this.filterAlumni.type) {
                    list = list.filter(a => a.jenis_campus === this.filterAlumni.type);
                }
                if (this.filterAlumni.track) {
                    list = list.filter(a => a.jalur_masuk === this.filterAlumni.track);
                }
                if (this.filterAlumni.year) {
                    list = list.filter(a => a.tahun_masuk == this.filterAlumni.year);
                }
                return list;
            }
        },

        mounted() {
            if (window.targetPendingTab) {
                this.activeTab = window.targetPendingTab;
                window.targetPendingTab = null;
            }
            // Siswa secara paksa tidak bisa mematikan masking
            if (this.isStudent) {
                this.privacyMask = true;
            }
            if (this.activeTab === 'tracking') {
                this.fetchAlumni();
                this.fetchCampuses();
            } else if (this.activeTab === 'kesiapan') {
                this.fetchKesiapan();
                this.fetchPdssMapels();
            } else if (this.activeTab === 'master_kampus') {
                this.fetchKampus();
            } else if (this.activeTab === 'master_jalur') {
                this.fetchJalur();
            } else if (this.activeTab === 'simulasi') {
                this.fetchSimulasiSettings();
                this.fetchSimulasi();
                if (this.listKampusFlat.length === 0) this.fetchKampusFlatList();
            }
        },

        methods: {
            async recalculateRanking() {
                if (!this.filterAcademicYear) {
                    this.showSelectTaWarning();
                    return;
                }
                this.recalculating = true;
                try {
                    let url = this.currentTenantId ? `${_baseUrl}/api/v1/pdss/recalc-kesiapan?tenant_id=${this.currentTenantId}` : `${_baseUrl}/api/v1/pdss/recalc-kesiapan`;
                    const res = await axios.post(url, { tahun_ajaran_id: this.filterAcademicYear });
                    if (res.data && res.data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil',
                            text: res.data.message || 'Ranking paralel berhasil dihitung ulang dan disimpan.',
                            confirmButtonColor: '#2563eb'
                        });
                        await this.fetchKesiapan();
                    } else {
                        const msg = (res.data && res.data.error) || 'Gagal menghitung ulang ranking.';
                        Swal.fire({ icon: 'warning', title: 'Perhatian', text: msg, confirmButtonColor: '#f8bb86' });
                    }
                } catch (e) {
                    console.error('Failed recalculating ranking', e);
                    const msg = e.response?.data?.error || 'Terjadi kesalahan sistem saat menghitung ulang ranking.';
                    Swal.fire({ icon: 'error', title: 'Gagal', text: msg, confirmButtonColor: '#2563eb' });
                } finally {
                    this.recalculating = false;
                }
            },

            async resetAllEligible() {
                if (this.locks[3]?.is_locked) {
                    Swal.fire({ icon: 'warning', title: 'Terkunci', text: 'Langkah 3 sedang dikunci. Silakan buka kunci terlebih dahulu.' });
                    return;
                }

                const result = await Swal.fire({
                    title: 'Reset Semua Siswa Eligible?',
                    html: `Tindakan ini akan <b>menghapus seluruh status kelayakan manual (BK Manual)</b> dan mengembalikan status kelayakan siswa 100% murni berdasarkan urutan ranking nilai rapor dan kuota akreditasi resmi.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc2626',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: '<i class="bi bi-arrow-counterclockwise"></i> Ya, Reset Semua',
                    cancelButtonText: 'Batal'
                });

                if (!result.isConfirmed) return;

                this.recalculating = true;
                try {
                    const url = this.currentTenantId ? `${_baseUrl}/api/v1/pdss/reset-eligible?tenant_id=${this.currentTenantId}` : `${_baseUrl}/api/v1/pdss/reset-eligible`;
                    const res = await axios.post(url, {
                        tahun_ajaran_id: this.filterAcademicYear
                    });

                    if (res.data && res.data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil Direset',
                            text: res.data.message || 'Seluruh status siswa eligible telah dikembalikan ke kalkulasi otomatis.',
                            timer: 2000,
                            showConfirmButton: false
                        });
                        await this.fetchKesiapan();
                    } else {
                        Swal.fire({ icon: 'error', title: 'Gagal', text: res.data?.error || 'Gagal mereset status eligible.' });
                    }
                } catch (e) {
                    console.error('Failed to reset eligible', e);
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: 'Gagal mereset status eligible: ' + (e.response?.data?.error || e.message)
                    });
                } finally {
                    this.recalculating = false;
                }
            },
            // ==========================================
            // MASTER KAMPUS & PRODI
            // ==========================================
            
            async importExcelData() {
                const fileInput = this.$refs.excelFileInput;
                if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
                    Swal.fire({icon: 'warning', title: 'Oops', text: 'Pilih file Excel terlebih dahulu.'});
                    return;
                }
                
                const file = fileInput.files[0];
                const formData = new FormData();
                formData.append('excel_file', file);
                
                this.importingExcel = true;
                try {
                    const res = await axios.post(`${_baseUrl}/api/v1/kampus/import`, formData, {
                        headers: { 'Content-Type': 'multipart/form-data' }
                    });
                    if (res.data.success) {
                        Swal.fire({
                            icon: 'success', 
                            title: 'Berhasil', 
                            text: res.data.message
                        });
                        this.modalImportExcel.show = false;
                        this.fetchKampus(); // Reload campus list
                    }
                } catch(e) {
                    if (e.response?.status !== 422) {
                        console.error(e);
                    }
                    const msg = (e.response && e.response.data && e.response.data.error) || 'Terjadi kesalahan saat mengunggah.';
                    if (e.response?.status === 422) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Format Template Salah',
                            text: msg,
                            confirmButtonColor: '#f8bb86'
                        });
                    } else {
                        Swal.fire({icon: 'error', title: 'Gagal', text: msg});
                    }
                } finally {
                    this.importingExcel = false;
                    if(fileInput) fileInput.value = '';
                }
            },

            async fetchKampus() {
                if (this.userRole === 'super_admin' && !this.currentTenantId) {
                    this.listKampus = [];
                    return;
                }
                this.loadingKampus = true;
                try {
                    const url = this.currentTenantId ? `${_baseUrl}/api/v1/kampus?tenant_id=${this.currentTenantId}` : `${_baseUrl}/api/v1/kampus`;
                    const res = await axios.get(url);
                    if(res.data.success) this.listKampus = res.data.data || [];
                } catch(e) {} finally { this.loadingKampus = false; }
            },
            openKampusModal(k = null) {
                if(k) {
                    this.modalMstKampus.form = { ...k };
                } else {
                    this.modalMstKampus.form = { id: '', nama_kampus: '', kota_kampus: '', alamat_kampus: '', jenis_kampus: 'Negeri' };
                }
                this.modalMstKampus.show = true;
            },
            async saveKampus() {
                try {
                    const url = this.currentTenantId ? `${_baseUrl}/api/v1/kampus?tenant_id=${this.currentTenantId}` : `${_baseUrl}/api/v1/kampus`;
                    const res = await axios.post(url, this.modalMstKampus.form);
                    if(res.data.success) {
                        Swal.fire({icon:'success', title:'Tersimpan', text:res.data.message});
                        this.modalMstKampus.show = false;
                        this.fetchKampus();
                    }
                } catch(e) { Swal.fire({icon:'error', title:'Gagal', text:'Terjadi kesalahan'}); }
            },
            async deleteKampus(id) {
                if(!await Swal.fire({title:'Hapus?', icon:'warning', showCancelButton:true}).then(r=>r.isConfirmed)) return;
                try {
                    const url = this.currentTenantId ? `${_baseUrl}/api/v1/kampus/delete?tenant_id=${this.currentTenantId}` : `${_baseUrl}/api/v1/kampus/delete`;
                    const res = await axios.post(url, {id});
                    if(res.data.success) this.fetchKampus();
                } catch(e) {}
            },
            
            // PRODI
            async manageProdi(kampus) {
                this.modalProdi.kampus = kampus;
                this.modalProdi.form.kampus_id = kampus.id;
                this.resetFormProdi();
                this.modalProdi.show = true;
                this.fetchProdi(kampus.id);
            },
            async fetchProdi(kampusId) {
                this.loadingProdi = true;
                try {
                    const url = this.currentTenantId ? `${_baseUrl}/api/v1/kampus/prodi?kampus_id=${kampusId}&tenant_id=${this.currentTenantId}` : `${_baseUrl}/api/v1/kampus/prodi?kampus_id=` + kampusId;
                    const res = await axios.get(url);
                    if(res.data.success) this.listProdi = res.data.data || [];
                } catch(e) {} finally { this.loadingProdi = false; }
            },
            resetFormProdi() {
                this.modalProdi.form = { id: '', kampus_id: this.modalProdi.kampus.id, kode_prodi: '', fakultas: '', program_studi: '', jenjang: 'S1', jenis_portofolio: '' };
            },
            editProdi(p) {
                this.modalProdi.form = { ...p };
            },
            async saveProdi() {
                try {
                    const url = this.currentTenantId ? `${_baseUrl}/api/v1/kampus/prodi?tenant_id=${this.currentTenantId}` : `${_baseUrl}/api/v1/kampus/prodi`;
                    const res = await axios.post(url, this.modalProdi.form);
                    if(res.data.success) {
                        this.resetFormProdi();
                        this.fetchProdi(this.modalProdi.kampus.id);
                        this.fetchKampus(); // Update prodi count
                    }
                } catch(e) {}
            },
            async deleteProdi(id) {
                if(!confirm('Hapus prodi ini?')) return;
                try {
                    const url = this.currentTenantId ? `${_baseUrl}/api/v1/kampus/prodi/delete?tenant_id=${this.currentTenantId}` : `${_baseUrl}/api/v1/kampus/prodi/delete`;
                    const res = await axios.post(url, {id});
                    if(res.data.success) {
                        this.fetchProdi(this.modalProdi.kampus.id);
                        this.fetchKampus();
                    }
                } catch(e) {}
            },
            
            // RIWAYAT KEKETATAN
            async manageRiwayatProdi(prodi) {
                if(this.modalProdi.expandedProdiId === prodi.id) {
                    this.modalProdi.expandedProdiId = null;
                    return;
                }
                this.modalProdi.expandedProdiId = prodi.id;
                this.formRiwayat = { prodi_id: prodi.id, tahun: new Date().getFullYear(), daya_tampung: 0, jumlah_pendaftar: 0 };
                this.fetchRiwayat(prodi.id);
            },
            async fetchRiwayat(prodiId) {
                try {
                    const url = this.currentTenantId ? `${_baseUrl}/api/v1/kampus/prodi/riwayat?prodi_id=${prodiId}&tenant_id=${this.currentTenantId}` : `${_baseUrl}/api/v1/kampus/prodi/riwayat?prodi_id=` + prodiId;
                    const res = await axios.get(url);
                    if(res.data.success) this.listRiwayat = res.data.data || [];
                } catch(e) {}
            },
            async saveRiwayat() {
                try {
                    const url = this.currentTenantId ? `${_baseUrl}/api/v1/kampus/prodi/riwayat?tenant_id=${this.currentTenantId}` : `${_baseUrl}/api/v1/kampus/prodi/riwayat`;
                    const res = await axios.post(url, this.formRiwayat);
                    if(res.data.success) this.fetchRiwayat(this.formRiwayat.prodi_id);
                } catch(e) {}
            },
            async deleteRiwayat(id) {
                if(!confirm('Hapus riwayat?')) return;
                try {
                    const url = this.currentTenantId ? `${_baseUrl}/api/v1/kampus/prodi/riwayat/delete?tenant_id=${this.currentTenantId}` : `${_baseUrl}/api/v1/kampus/prodi/riwayat/delete`;
                    const res = await axios.post(url, {id});
                    if(res.data.success) this.fetchRiwayat(this.formRiwayat.prodi_id);
                } catch(e) {}
            },

            // JALUR MASUK
            async fetchJalur() {
                if (this.userRole === 'super_admin' && !this.currentTenantId) {
                    this.listJalur = [];
                    return;
                }
                this.loadingJalur = true;
                try {
                    const url = this.currentTenantId ? `${_baseUrl}/api/v1/kampus/jalur?tenant_id=${this.currentTenantId}` : `${_baseUrl}/api/v1/kampus/jalur`;
                    const res = await axios.get(url);
                    if(res.data.success) this.listJalur = res.data.data || [];
                } catch(e) {} finally { this.loadingJalur = false; }
            },
            openJalurModal(j = null) {
                if(j) this.modalMstJalur.form = { ...j };
                else this.modalMstJalur.form = { id: '', nama_jalur: '', kategori: 'Lainnya' };
                this.modalMstJalur.show = true;
            },
            async saveJalur() {
                try {
                    const url = this.currentTenantId ? `${_baseUrl}/api/v1/kampus/jalur?tenant_id=${this.currentTenantId}` : `${_baseUrl}/api/v1/kampus/jalur`;
                    const res = await axios.post(url, this.modalMstJalur.form);
                    if(res.data.success) {
                        this.modalMstJalur.show = false;
                        this.fetchJalur();
                    }
                } catch(e) {}
            },
            async deleteJalur(id) {
                if(!confirm('Hapus jalur?')) return;
                try {
                    const url = this.currentTenantId ? `${_baseUrl}/api/v1/kampus/jalur/delete?tenant_id=${this.currentTenantId}` : `${_baseUrl}/api/v1/kampus/jalur/delete`;
                    const res = await axios.post(url, {id});
                    if(res.data.success) this.fetchJalur();
                } catch(e) {}
            },

            async refreshAll() {
                this.loading = true;
                try {
                    // 1. Ambil data kesiapan terlebih dahulu (menginisialisasi tahun ajaran)
                    await this.fetchKesiapan();
                    
                    // 2. Ambil data lainnya secara paralel setelah tahun ajaran siap
                    await Promise.all([
                        this.fetchPdssMapels(),
                        this.fetchAlumni(),
                        this.fetchCampuses()
                    ]);
                } catch (e) {
                    console.error('Error refreshing PDSS data', e);
                } finally {
                    this.loading = false;
                }
            },

            // ─── DATA FETCHING ───────────────────────────────────
            async fetchKesiapan() {
                try {
                    let url = this.currentTenantId ? `${_baseUrl}/api/v1/pdss/kesiapan?tenant_id=${this.currentTenantId}` : `${_baseUrl}/api/v1/pdss/kesiapan`;
                    if (this.filterAcademicYear) {
                        url += (url.includes('?') ? '&' : '?') + 'tahun_ajaran_id=' + encodeURIComponent(this.filterAcademicYear);
                    }
                    const res = await axios.get(url);
                    if (res.data.success) {
                        this.students = res.data.data || [];
                        this.accreditation = res.data.accreditation || 'A';
                        this.mapelNotConfigured = res.data.mapel_not_configured || false;
                        this.totalConfiguredMapels = res.data.total_configured_mapels || 0;
                        this.totalConfiguredSemesters = res.data.total_configured_semesters || 0;
                        this.academicYears = res.data.years || [];
                        
                        // Set default academic year if not chosen
                        if (!this.filterAcademicYear && this.academicYears.length > 0) {
                            const active = this.academicYears.find(y => y.is_active === true || y.is_active == 1 || y.is_active === 't');
                            if (active) this.filterAcademicYear = active.id;
                            else this.filterAcademicYear = this.academicYears[0].id;
                        }

                        // Muat mapel setelah tahun ajaran siap
                        this.fetchPdssMapels();

                        this.locks = res.data.locks || {
                            1: { is_locked: false, locked_by: null, locked_at: null },
                            2: { is_locked: false, locked_by: null, locked_at: null },
                            3: { is_locked: false, locked_by: null, locked_at: null },
                            4: { is_locked: false, locked_by: null, locked_at: null }
                        };

                        // Auto-set default quota percentage based on accreditation (draft/unlocked only)
                        if (!this.locks[3]?.is_locked) {
                            let base = 5;
                            if (this.accreditation.includes('A')) base = 40;
                            else if (this.accreditation.includes('B')) base = 25;
                            
                            this.quotaPercent = this.useERapor ? (base + 5) : base;
                        }
                    }
                } catch (e) {
                    console.error('Failed fetching PDSS stats', e);
                }
            },

            showSelectTaWarning() {
                Swal.fire({
                    icon: 'warning',
                    title: 'Pilih Tahun Ajaran',
                    text: 'Silakan pilih Tahun Ajaran terlebih dahulu sebelum mengunduh leger nilai.',
                    confirmButtonColor: '#3085d6'
                });
            },

            async onAcademicYearChange() {
                this.loading = true;
                try {
                    await Promise.all([
                        this.fetchKesiapan(),
                        this.fetchPdssMapels()
                    ]);
                } catch (e) {
                    console.error('Error changing academic year', e);
                } finally {
                    this.loading = false;
                }
            },

            async fetchPdssMapels() {
                this.loadingMapels = true;
                try {
                    let url = this.currentTenantId ? `${_baseUrl}/api/v1/pdss/config-mapel?tenant_id=${this.currentTenantId}` : `${_baseUrl}/api/v1/pdss/config-mapel`;
                    if (this.filterAcademicYear) {
                        url += (url.includes('?') ? '&' : '?') + 'tahun_ajaran_id=' + encodeURIComponent(this.filterAcademicYear);
                    }
                    const res = await axios.get(url);
                    if (res.data.success) {
                        this.pdssMapels = res.data.data || [];
                        this.masterMajors = res.data.majors || [];
                        this.mapelCategories = res.data.categories || [];
                    }
                } catch (e) {
                    console.error('Failed fetching PDSS mapels', e);
                } finally {
                    this.loadingMapels = false;
                }
            },

            getMapelCategoryBadge(kat) {
                const k = (kat || '').toLowerCase();
                if (k.includes('wajib') || k.includes('nasional') || k.includes('umum')) {
                    return 'bg-blue-100 text-blue-800 border border-blue-200';
                }
                if (k.includes('mipa') || k.includes('ipa')) {
                    return 'bg-emerald-100 text-emerald-800 border border-emerald-200';
                }
                if (k.includes('ips')) {
                    return 'bg-amber-100 text-amber-800 border border-amber-200';
                }
                if (k.includes('bahasa')) {
                    return 'bg-purple-100 text-purple-800 border border-purple-200';
                }
                return 'bg-slate-100 text-slate-700 border border-slate-200';
            },

            autoDetectMapelsFromGrades() {
                if (this.locks[1]?.is_locked) return;
                let detectedCount = 0;
                this.pdssMapels.forEach(m => {
                    let hasAny = false;
                    for (let s = 1; s <= 6; s++) {
                        if (m['has_sem_' + s]) {
                            m['sem_' + s] = true;
                            hasAny = true;
                        } else {
                            m['sem_' + s] = false;
                        }
                    }
                    if (hasAny) detectedCount++;
                });
                Swal.fire({
                    icon: 'success',
                    title: 'Deteksi Otomatis Berhasil',
                    text: `${detectedCount} mata pelajaran yang memiliki nilai riil di buku induk berhasil dicentang otomatis.`,
                    timer: 2000,
                    showConfirmButton: false
                });
            },

            applySnbpPreset() {
                if (this.locks[1]?.is_locked) return;
                const wajibKeywords = ['matematika', 'bahasa indonesia', 'bahasa inggris', 'agama', 'ppkn', 'pancasila', 'sejarah'];
                
                this.pdssMapels.forEach(m => {
                    const name = (m.nama_mapel || '').toLowerCase();
                    const kat = (m.kategori || '').toLowerCase();
                    
                    const isWajib = wajibKeywords.some(kw => name.includes(kw)) || kat.includes('wajib') || kat.includes('nasional');
                    if (isWajib) {
                        m.sem_1 = true;
                        m.sem_2 = true;
                        m.sem_3 = true;
                        m.sem_4 = true;
                        m.sem_5 = true;
                        m.sem_6 = false;
                    }
                });
                Swal.fire({
                    icon: 'info',
                    title: 'Preset SNBP Diaktifkan',
                    text: 'Mata pelajaran muatan wajib nasional telah dicentang untuk Semester 1 s.d. 5.',
                    timer: 1800,
                    showConfirmButton: false
                });
            },

            selectAllMapels(semesters = [1, 2, 3, 4, 5]) {
                if (this.locks[1]?.is_locked) return;
                this.filteredPdssMapels.forEach(m => {
                    semesters.forEach(s => {
                        m['sem_' + s] = true;
                    });
                });
            },

            clearAllMapels() {
                if (this.locks[1]?.is_locked) return;
                this.filteredPdssMapels.forEach(m => {
                    for (let s = 1; s <= 6; s++) {
                        m['sem_' + s] = false;
                    }
                });
            },

            async savePdssMapels() {
                if (this.locks[1]?.is_locked) return;
                const configs = this.pdssMapels.map(m => ({
                    mapel_id: m.id,
                    jurusan_id: m.jurusan_id || null,
                    sem_1: m.sem_1 ? 1 : 0,
                    sem_2: m.sem_2 ? 1 : 0,
                    sem_3: m.sem_3 ? 1 : 0,
                    sem_4: m.sem_4 ? 1 : 0,
                    sem_5: m.sem_5 ? 1 : 0,
                    sem_6: m.sem_6 ? 1 : 0
                }));
                this.savingMapels = true;
                try {
                    const url = this.currentTenantId ? `${_baseUrl}/api/v1/pdss/config-mapel?tenant_id=${this.currentTenantId}` : `${_baseUrl}/api/v1/pdss/config-mapel`;
                    const selectedMajObj = this.masterMajors.find(m => m.nama_jurusan === this.filterMapelMajor || m.id === this.filterMapelMajor);
                    const payloadJurusanId = selectedMajObj ? selectedMajObj.id : null;
                    const res = await axios.post(url, { 
                        configs, 
                        tahun_ajaran_id: this.filterAcademicYear,
                        jurusan_id: payloadJurusanId
                    });
                    if (res.data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil',
                            text: res.data.message
                        });
                        this.showMapelConfig = false;
                        this.fetchKesiapan();
                    }
                } catch (e) {
                    console.error('Failed saving PDSS mapels', e);
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: e.response?.data?.error || 'Gagal menyimpan pilihan mata pelajaran.'
                    });
                } finally {
                    this.savingMapels = false;
                }
            },

            async saveManualEligible(siswaId, status) {
                if (this.locks[4]?.is_locked) return;
                try {
                    const url = this.currentTenantId ? `${_baseUrl}/api/v1/pdss/manual-eligible?tenant_id=${this.currentTenantId}` : `${_baseUrl}/api/v1/pdss/manual-eligible`;
                    const res = await axios.post(url, { siswa_id: siswaId, status_eligible: status });
                    if (res.data.success) {
                        this.fetchKesiapan();
                    }
                } catch (e) {
                    console.error('Failed updating manual eligibility', e);
                    const status = e.response ? e.response.status : 500;
                    const msg = e.response?.data?.error || 'Gagal merubah status kelayakan.';
                    if (status === 400 || status === 422) {
                        Swal.fire({ icon: 'warning', title: 'Perhatian', text: msg, confirmButtonColor: '#f8bb86' });
                    } else {
                        Swal.fire({ icon: 'error', title: 'Gagal', text: msg, confirmButtonColor: '#2563eb' });
                    }
                }
            },

            // ─── PENGUNDURAN DIRI METHODS ───────────────────────
            openModalPengunduranDiri(stu) {
                this.modalPengunduranDiri.siswa = stu;
                this.modalPengunduranDiri.nomor_surat = '';
                this.modalPengunduranDiri.tanggal_surat = new Date().toISOString().slice(0, 10);
                this.modalPengunduranDiri.alasan = '';
                this.modalPengunduranDiri.file = null;
                this.modalPengunduranDiri.saving = false;
                this.modalPengunduranDiri.show = true;
                this.$nextTick(() => {
                    if (this.$refs.suratPengunduranFileInput) {
                        this.$refs.suratPengunduranFileInput.value = '';
                    }
                });
            },

            handleSuratFileChange(e) {
                this.modalPengunduranDiri.file = e.target.files[0] || null;
            },

            async submitPengunduranDiri() {
                if (!this.modalPengunduranDiri.file) {
                    Swal.fire({ icon: 'warning', title: 'File Surat Wajib', text: 'Silakan pilih file scan surat pernyataan pengunduran diri.' });
                    return;
                }

                this.modalPengunduranDiri.saving = true;
                const formData = new FormData();
                formData.append('siswa_id', this.modalPengunduranDiri.siswa.id);
                formData.append('tahun_ajaran_id', this.filterAcademicYear);
                formData.append('nomor_surat', this.modalPengunduranDiri.nomor_surat);
                formData.append('tanggal_surat', this.modalPengunduranDiri.tanggal_surat);
                formData.append('alasan', this.modalPengunduranDiri.alasan);
                formData.append('surat_file', this.modalPengunduranDiri.file);

                try {
                    let url = `${_baseUrl}/api/v1/pdss/pengunduran-diri/save`;
                    if (this.currentTenantId) url += `?tenant_id=${this.currentTenantId}`;
                    const res = await axios.post(url, formData, {
                        headers: { 'Content-Type': 'multipart/form-data' }
                    });
                    if (res.data && res.data.success) {
                        this.modalPengunduranDiri.show = false;
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil',
                            text: res.data.message || 'Pengunduran diri berhasil diproses dan kuota telah dilimpahkan.',
                            confirmButtonColor: '#2563eb'
                        });
                        await this.fetchKesiapan();
                    } else {
                        Swal.fire({ icon: 'error', title: 'Gagal', text: (res.data && res.data.error) || 'Gagal memproses pengunduran diri.' });
                    }
                } catch (e) {
                    console.error('Failed submitting pengunduran diri', e);
                    Swal.fire({ icon: 'error', title: 'Gagal', text: e.response?.data?.error || 'Terjadi kesalahan sistem saat mengunggah surat.' });
                } finally {
                    this.modalPengunduranDiri.saving = false;
                }
            },

            async viewSuratPengunduranDiri(stu) {
                this.modalDetailSurat.data = null;
                this.modalDetailSurat.loading = true;
                this.modalDetailSurat.show = true;

                try {
                    let url = `${_baseUrl}/api/v1/pdss/pengunduran-diri/detail?siswa_id=${stu.id}`;
                    if (this.filterAcademicYear) url += `&tahun_ajaran_id=${this.filterAcademicYear}`;
                    if (this.currentTenantId) url += `&tenant_id=${this.currentTenantId}`;
                    const res = await axios.get(url);
                    if (res.data && res.data.success) {
                        this.modalDetailSurat.data = res.data.data;
                    } else {
                        Swal.fire({ icon: 'warning', title: 'Perhatian', text: res.data.error || 'Data surat tidak ditemukan.' });
                        this.modalDetailSurat.show = false;
                    }
                } catch (e) {
                    console.error('Failed fetching detail surat', e);
                    Swal.fire({ icon: 'error', title: 'Gagal', text: 'Gagal memuat detail surat pengunduran diri.' });
                    this.modalDetailSurat.show = false;
                } finally {
                    this.modalDetailSurat.loading = false;
                }
            },

            async cancelPengunduranDiri(stu) {
                const confirm = await Swal.fire({
                    title: 'Batalkan Pengunduran Diri?',
                    text: `Status "${stu.nama_lengkap}" akan dikembalikan ke daftar ranking dan kuota pelimpahan akan disesuaikan kembali.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#2563eb',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Ya, Batalkan Status Mundur',
                    cancelButtonText: 'Tutup'
                });
                if (!confirm.isConfirmed) return;

                try {
                    let url = `${_baseUrl}/api/v1/pdss/pengunduran-diri/cancel`;
                    if (this.currentTenantId) url += `?tenant_id=${this.currentTenantId}`;
                    const res = await axios.post(url, {
                        siswa_id: stu.id,
                        tahun_ajaran_id: this.filterAcademicYear
                    });
                    if (res.data && res.data.success) {
                        Swal.fire({ icon: 'success', title: 'Berhasil', text: res.data.message || 'Pengunduran diri berhasil dibatalkan.' });
                        await this.fetchKesiapan();
                    } else {
                        Swal.fire({ icon: 'error', title: 'Gagal', text: res.data.error || 'Gagal membatalkan status.' });
                    }
                } catch (e) {
                    console.error('Failed cancelling pengunduran diri', e);
                    Swal.fire({ icon: 'error', title: 'Gagal', text: 'Terjadi kesalahan sistem saat membatalkan status.' });
                }
            },

            async togglePdssLock(step) {
                const stepNames = {
                    1: 'Langkah 1: Pemilihan Mata Pelajaran PDSS',
                    2: 'Langkah 2: Pengecekan Nilai Rapor & Leger',
                    3: 'Langkah 3: Ranking Paralel & Kuota Akreditasi',
                    4: 'Langkah 4: Finalisasi Siswa Eligible'
                };
                const stepName = stepNames[step] || `Langkah ${step}`;
                const targetLockState = !this.locks[step]?.is_locked;
                const actionText = targetLockState ? 'mengunci' : 'membuka kunci';
                const confirmText = targetLockState 
                    ? `${stepName} akan dibekukan dan dilanjutkan ke langkah berikutnya.` 
                    : `Anda akan dapat mengubah ${stepName} kembali.`;

                const result = await Swal.fire({
                    title: `Apakah Anda yakin ingin ${actionText} ${stepName}?`,
                    text: confirmText,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Lanjutkan',
                    cancelButtonText: 'Batal'
                });

                if (!result.isConfirmed) return;

                try {
                    const url = this.currentTenantId ? `${_baseUrl}/api/v1/pdss/lock?tenant_id=${this.currentTenantId}` : `${_baseUrl}/api/v1/pdss/lock`;
                    const res = await axios.post(url, { step: step, is_locked: targetLockState, tahun_ajaran_id: this.filterAcademicYear });
                    if (res.data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil',
                            text: `Status penguncian ${stepName} berhasil diperbarui.`
                        });
                        this.fetchKesiapan();
                    }
                } catch (e) {
                    console.error('Failed toggling PDSS lock', e);
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: e.response?.data?.error || 'Gagal mengubah status penguncian.'
                    });
                }
            },

            async showAuditModal(siswaId) {
                this.auditGrades = null;
                const modalEl = document.getElementById('modalAuditGrades');
                let myModal = bootstrap.Modal.getInstance(modalEl);
                if (!myModal) {
                    myModal = new bootstrap.Modal(modalEl);
                }
                myModal.show();
                
                try {
                    let url = this.currentTenantId 
                        ? `${_baseUrl}/api/v1/pdss/student-grades?siswa_id=${encodeURIComponent(siswaId)}&tenant_id=${encodeURIComponent(this.currentTenantId)}` 
                        : `${_baseUrl}/api/v1/pdss/student-grades?siswa_id=${encodeURIComponent(siswaId)}`;
                    if (this.filterAcademicYear) {
                        url += '&tahun_ajaran_id=' + encodeURIComponent(this.filterAcademicYear);
                    }
                    const res = await axios.get(url);
                    if (res.data.success) {
                        const payload = res.data.data || res.data;
                        this.auditGrades = {
                            student: payload.student || {},
                            grades: payload.grades || [],
                            semester_avgs: payload.semester_avgs || {},
                            total_nilai: payload.total_nilai || 0,
                            total_mapel_diambil: payload.total_mapel_diambil || 0,
                            rata_rata: payload.rata_rata || 0
                        };
                    }
                } catch (e) {
                    console.error('Failed fetching audit grades', e);
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: 'Gagal memuat rincian nilai siswa: ' + (e.response?.data?.error || e.message)
                    });
                }
            },

            async fetchAlumni() {
                if (this.userRole === 'super_admin' && !this.currentTenantId) {
                    this.alumniData = [];
                    return;
                }
                try {
                    const url = this.currentTenantId ? `${_baseUrl}/api/v1/pdss/alumni-tracks?tenant_id=${this.currentTenantId}` : `${_baseUrl}/api/v1/pdss/alumni-tracks`;
                    const res = await axios.get(url);
                    if (res.data.success) {
                        this.alumniData = res.data.data || [];
                    }
                } catch (e) {
                    console.error('Failed fetching alumni tracks', e);
                }
            },

            async fetchCampuses() {
                if (this.userRole === 'super_admin' && !this.currentTenantId) {
                    this.campusData = [];
                    return;
                }
                try {
                    const url = this.currentTenantId ? `${_baseUrl}/api/v1/pdss/target-kampus?tenant_id=${this.currentTenantId}` : `${_baseUrl}/api/v1/pdss/target-kampus`;
                    const res = await axios.get(url);
                    if (res.data.success) {
                        this.campusData = res.data.data || [];
                    }
                } catch (e) {
                    console.error('Failed fetching target campuses', e);
                }
            },

             // ─── ALUMNI CRUD ─────────────────────────────────────
             openAlumniModal(alumniRecord = null) {
                 this.searchResults = [];
                 this.showSearchDropdown = false;
                 if (alumniRecord) {
                     this.modalAlumni.form = {
                         id: alumniRecord.id,
                         id_siswa: alumniRecord.id_siswa || '',
                         nama_alumni: alumniRecord.nama_alumni || '',
                         tahun_masuk: alumniRecord.tahun_masuk,
                         jenis_kampus: alumniRecord.jenis_campus || alumniRecord.jenis_kampus || 'Negeri',
                         jalur_masuk: alumniRecord.jalur_masuk || 'SNBP',
                         universitas_nama: alumniRecord.universitas_nama || '',
                         jurusan_nama: alumniRecord.jurusan_nama || '',
                         status: alumniRecord.status || 'Aktif'
                     };
                 } else {
                     this.modalAlumni.form = {
                         id: '',
                         id_siswa: '',
                         nama_alumni: '',
                         tahun_masuk: new Date().getFullYear(),
                         jenis_kampus: 'Negeri',
                         jalur_masuk: 'SNBP',
                         universitas_nama: '',
                         jurusan_nama: '',
                         status: 'Aktif'
                     };
                     this.modalAlumni.selectedStudent = null;
                 }
                 this.modalAlumni.show = true;
             },

             // ─── STUDENT SEARCH FOR ALUMNI ───────────────────────
             async searchStudents() {
                 this.modalAlumni.selectedStudent = null;
                 const query = this.modalAlumni.form.nama_alumni.trim();
                 if (query.length < 2) {
                     this.searchResults = [];
                     this.showSearchDropdown = false;
                     return;
                 }

                 this.searchingStudents = true;
                 try {
                     const url = this.currentTenantId ? `${_baseUrl}/api/v1/pdss/students/search?q=${encodeURIComponent(query)}&tenant_id=${this.currentTenantId}` : `${_baseUrl}/api/v1/pdss/students/search?q=${encodeURIComponent(query)}`;
                     const res = await axios.get(url);
                     if (res.data.success) {
                         this.searchResults = res.data.data || [];
                         this.showSearchDropdown = true;
                     }
                 } catch (e) {
                     console.error('Failed searching students', e);
                 } finally {
                     this.searchingStudents = false;
                 }
             },

             selectStudent(student) {
                 this.modalAlumni.form.nama_alumni = student.nama_lengkap;
                 this.modalAlumni.form.id_siswa = student.id;
                 this.modalAlumni.selectedStudent = student;
                 this.showSearchDropdown = false;
                 this.searchResults = [];
             },

             hideSearchDropdownWithDelay() {
                 setTimeout(() => {
                     this.showSearchDropdown = false;
                 }, 200);
             },

            async saveAlumniTrack() {
                try {
                    const url = this.currentTenantId ? `${_baseUrl}/api/v1/pdss/alumni-tracks?tenant_id=${this.currentTenantId}` : `${_baseUrl}/api/v1/pdss/alumni-tracks`;
                    const res = await axios.post(url, this.modalAlumni.form);
                    if (res.data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil',
                            text: res.data.message,
                            confirmButtonColor: '#2563eb'
                        });
                        this.modalAlumni.show = false;
                        this.fetchAlumni();
                    }
                } catch (e) {
                    const msg = (e.response && e.response.data && e.response.data.error) || 'Gagal menyimpan data alumni.';
                    Swal.fire({ icon: 'error', title: 'Gagal', text: msg, confirmButtonColor: '#2563eb' });
                }
            },

            async deleteAlumniTrack(alumniRecord) {
                const confirm = await Swal.fire({
                    title: 'Apakah Anda yakin?',
                    text: `Menghapus data tracking untuk "${alumniRecord.nama_alumni}" di ${alumniRecord.universitas_nama}.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal'
                });

                if (!confirm.isConfirmed) return;

                try {
                    const url = this.currentTenantId ? `${_baseUrl}/api/v1/pdss/alumni-tracks/delete?tenant_id=${this.currentTenantId}` : `${_baseUrl}/api/v1/pdss/alumni-tracks/delete`;
                    const res = await axios.post(url, { id: alumniRecord.id });
                    if (res.data.success) {
                        Swal.fire({ icon: 'success', title: 'Terhapus', text: res.data.message, confirmButtonColor: '#2563eb' });
                        this.fetchAlumni();
                    }
                } catch (e) {
                    const msg = (e.response && e.response.data && e.response.data.error) || 'Gagal menghapus data.';
                    Swal.fire({ icon: 'error', title: 'Gagal', text: msg, confirmButtonColor: '#2563eb' });
                }
            },

            // Privacy Name Masking Utility
            maskName(name) {
                if (!name) return '';
                const parts = name.trim().split(' ');
                const maskedParts = parts.map(p => {
                    if (p.length <= 1) return p;
                    if (p.length === 2) return p[0] + '*';
                    return p[0] + '*'.repeat(p.length - 2) + p[p.length - 1];
                });
                return maskedParts.join(' ');
            },

            // ─── CAMPUS TARGETS CRUD ─────────────────────────────
            openCampusModal(campusRecord = null) {
                if (campusRecord) {
                    this.modalCampus.form = { ...campusRecord };
                } else {
                    this.modalCampus.form = {
                        id: '',
                        nama_kampus: '',
                        jenis_kampus: 'Negeri',
                        kuota_target: 5
                    };
                }
                this.modalCampus.show = true;
            },

            async saveTargetKampus() {
                try {
                    const url = this.currentTenantId ? `${_baseUrl}/api/v1/pdss/target-kampus?tenant_id=${this.currentTenantId}` : `${_baseUrl}/api/v1/pdss/target-kampus`;
                    const res = await axios.post(url, this.modalCampus.form);
                    if (res.data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil',
                            text: res.data.message,
                            confirmButtonColor: '#2563eb'
                        });
                        this.modalCampus.show = false;
                        this.fetchCampuses();
                    }
                } catch (e) {
                    const msg = (e.response && e.response.data && e.response.data.error) || 'Gagal menyimpan target kampus.';
                    Swal.fire({ icon: 'error', title: 'Gagal', text: msg, confirmButtonColor: '#2563eb' });
                }
            },

            async deleteTargetKampus(campusRecord) {
                const confirm = await Swal.fire({
                    title: 'Hapus Target Kampus?',
                    text: `Anda akan menghapus target "${campusRecord.nama_kampus}". Tindakan ini tidak dapat dibatalkan.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal'
                });

                if (!confirm.isConfirmed) return;

                try {
                    const url = this.currentTenantId ? `${_baseUrl}/api/v1/pdss/target-kampus/delete?tenant_id=${this.currentTenantId}` : `${_baseUrl}/api/v1/pdss/target-kampus/delete`;
                    const res = await axios.post(url, { id: campusRecord.id });
                    if (res.data.success) {
                        Swal.fire({ icon: 'success', title: 'Terhapus', text: res.data.message, confirmButtonColor: '#2563eb' });
                        this.fetchCampuses();
                    }
                } catch (e) {
                    const msg = (e.response && e.response.data && e.response.data.error) || 'Gagal menghapus data.';
                    Swal.fire({ icon: 'error', title: 'Gagal', text: msg, confirmButtonColor: '#2563eb' });
                }
            },

            async seedDefaultCampuses() {
                this.loading = true;
                try {
                    const url = this.currentTenantId ? `${_baseUrl}/api/v1/pdss/target-kampus/seed?tenant_id=${this.currentTenantId}` : `${_baseUrl}/api/v1/pdss/target-kampus/seed`;
                    const res = await axios.post(url);
                    if (res.data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil',
                            text: res.data.message,
                            confirmButtonColor: '#2563eb'
                        });
                        this.fetchCampuses();
                    }
                } catch (e) {
                    const msg = (e.response && e.response.data && e.response.data.error) || 'Gagal melakukan seeding data target kampus.';
                    Swal.fire({ icon: 'error', title: 'Gagal', text: msg, confirmButtonColor: '#2563eb' });
                } finally {
                    this.loading = false;
                }
            },

            // ============================================================
            // SIMULASI PEMILIHAN KAMPUS METHODS
            // ============================================================
            async fetchSimulasiSettings() {
                if (!this.filterAcademicYear) return;
                try {
                    let url = `${_baseUrl}/api/v1/pdss/simulasi/setting?tahun_ajaran_id=${this.filterAcademicYear}`;
                    if (this.currentTenantId) url += `&tenant_id=${this.currentTenantId}`;
                    const res = await axios.get(url);
                    if (res.data.success) {
                        this.simulasiSettings = res.data.data;
                    }
                } catch (e) {
                    console.error('Gagal memuat setting simulasi', e);
                }
            },

            async toggleSimulasiSetting(noSim, action) {
                const label = action === 'open' ? 'membuka' : (action === 'close' ? 'menutup' : 'mengunci');
                const confirm = await Swal.fire({
                    title: 'Apakah Anda yakin?',
                    text: `Anda akan ${label} pengisian untuk Simulasi ${noSim}.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#2563eb',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Ya, Lanjutkan'
                });
                if (!confirm.isConfirmed) return;

                try {
                    let url = `${_baseUrl}/api/v1/pdss/simulasi/setting`;
                    if (this.currentTenantId) url += `?tenant_id=${this.currentTenantId}`;
                    const res = await axios.post(url, {
                        no_simulasi: noSim,
                        action: action,
                        tahun_ajaran_id: this.filterAcademicYear
                    });
                    if (res.data && res.data.success) {
                        Swal.fire({ icon: 'success', title: 'Berhasil', text: res.data.message, confirmButtonColor: '#2563eb' });
                        this.fetchSimulasiSettings();
                        this.fetchSimulasi();
                    } else {
                        const msg = (res.data && res.data.error) || 'Gagal mengubah status simulasi.';
                        Swal.fire({ icon: 'warning', title: 'Perhatian', text: msg, confirmButtonColor: '#f8bb86' });
                    }
                } catch (e) {
                    const msg = (e.response && e.response.data && e.response.data.error) || 'Gagal mengubah status simulasi.';
                    Swal.fire({ icon: 'warning', title: 'Perhatian', text: msg, confirmButtonColor: '#f8bb86' });
                }
            },

            async fetchSimulasi() {
                if (!this.filterAcademicYear) return;
                this.loadingSimulasi = true;
                try {
                    let url = `${_baseUrl}/api/v1/pdss/simulasi?tahun_ajaran_id=${this.filterAcademicYear}&no_simulasi=${this.activeNoSimulasi}`;
                    if (this.currentTenantId) url += `&tenant_id=${this.currentTenantId}`;
                    const res = await axios.get(url);
                    if (res.data.success) {
                        this.simulasiData = res.data.data;
                        this.simulasiStats = res.data.stats;
                    }
                } catch (e) {
                    console.error('Gagal memuat data simulasi', e);
                } finally {
                    this.loadingSimulasi = false;
                }
            },

            async fetchKampusFlatList() {
                try {
                    let url = `${_baseUrl}/api/v1/kampus`;
                    if (this.currentTenantId) url += `?tenant_id=${this.currentTenantId}`;
                    const res = await axios.get(url);
                    if (res.data.success) {
                        this.listKampusFlat = res.data.data;
                    }
                } catch (e) {
                    console.error('Gagal memuat daftar kampus', e);
                }
            },

            async fetchProdiByKampus(kampusId) {
                if (!kampusId) return;
                if (this.listProdiByKampus[kampusId]) return; // Cache hit

                try {
                    let url = `${_baseUrl}/api/v1/kampus/prodi?kampus_id=${kampusId}`;
                    if (this.currentTenantId) url += `&tenant_id=${this.currentTenantId}`;
                    const res = await axios.get(url);
                    if (res.data.success) {
                        // Gunakan spread operator agar properti dinamis terdeteksi reaktif oleh Vue
                        this.listProdiByKampus = {
                            ...this.listProdiByKampus,
                            [kampusId]: res.data.data
                        };
                    }
                } catch (e) {
                    console.error('Gagal memuat prodi', e);
                }
            },

            onKampusChange(slot) {
                const kid = slot === 1 ? this.modalSimulasi.form.kampus_id_1 : this.modalSimulasi.form.kampus_id_2;
                if (slot === 1) {
                    this.modalSimulasi.form.prodi_id_1 = '';
                } else {
                    this.modalSimulasi.form.prodi_id_2 = '';
                }
                if (kid) {
                    this.fetchProdiByKampus(kid);
                }
            },

            selectKampus(slot, kampus) {
                if (slot === 1) {
                    this.modalSimulasi.form.kampus_id_1 = kampus ? kampus.id : '';
                    this.modalSimulasi.showDropdown1 = false;
                    this.onKampusChange(1);
                } else {
                    this.modalSimulasi.form.kampus_id_2 = kampus ? kampus.id : '';
                    this.modalSimulasi.showDropdown2 = false;
                    this.onKampusChange(2);
                }
            },

            getKampusName(kampusId) {
                if (!kampusId) return '';
                const k = this.listKampusFlat.find(c => c.id === kampusId);
                return k ? k.nama_kampus : '';
            },

            openModalSimulasi(siswa) {
                this.modalSimulasi.siswa = siswa;
                this.modalSimulasi.conflictMsg = '';
                this.modalSimulasi.searchKampus1 = '';
                this.modalSimulasi.searchKampus2 = '';
                this.modalSimulasi.showDropdown1 = false;
                this.modalSimulasi.showDropdown2 = false;
                this.modalSimulasi.form.kampus_id_1 = siswa.kampus_id_1 || '';
                this.modalSimulasi.form.prodi_id_1 = siswa.prodi_id_1 || '';
                this.modalSimulasi.form.kampus_id_2 = siswa.kampus_id_2 || '';
                this.modalSimulasi.form.prodi_id_2 = siswa.prodi_id_2 || '';
                this.modalSimulasi.form.catatan_siswa = siswa.catatan_siswa || '';

                if (siswa.kampus_id_1) this.fetchProdiByKampus(siswa.kampus_id_1);
                if (siswa.kampus_id_2) this.fetchProdiByKampus(siswa.kampus_id_2);

                this.modalSimulasi.show = true;
            },

            openModalDetailKonflik(siswa, slot) {
                const conflicts = slot === 1 ? (siswa.konflik_detail_1 || []) : (siswa.konflik_detail_2 || []);
                const kampusNama = slot === 1 ? siswa.kampus_nama_1 : siswa.kampus_nama_2;
                const prodiNama = slot === 1 ? siswa.prodi_nama_1 : siswa.prodi_nama_2;
                this.modalDetailKonflik = {
                    show: true,
                    siswa: siswa,
                    slot: slot,
                    kampusNama: kampusNama || 'Kampus',
                    prodiNama: prodiNama || 'Program Studi',
                    conflicts: conflicts
                };
            },

            async submitSimulasi() {
                this.modalSimulasi.saving = true;
                try {
                    let url = `${_baseUrl}/api/v1/pdss/simulasi`;
                    if (this.currentTenantId) url += `?tenant_id=${this.currentTenantId}`;
                    const payload = {
                        siswa_id: this.modalSimulasi.siswa.siswa_id,
                        tahun_ajaran_id: this.filterAcademicYear,
                        no_simulasi: this.activeNoSimulasi,
                        ...this.modalSimulasi.form
                    };
                    const res = await axios.post(url, payload);
                    if (res.data.success) {
                        this.modalSimulasi.show = false;
                        this.fetchSimulasi();

                        if (res.data.warning) {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Disimpan Dengan Peringatan',
                                text: res.data.conflict_message,
                                confirmButtonColor: '#eab308'
                            });
                        } else {
                            Swal.fire({ icon: 'success', title: 'Berhasil', text: res.data.message, confirmButtonColor: '#2563eb' });
                        }
                    }
                } catch (e) {
                    const msg = (e.response && e.response.data && e.response.data.error) || 'Gagal menyimpan pilihan simulasi.';
                    Swal.fire({ icon: 'error', title: 'Gagal', text: msg, confirmButtonColor: '#2563eb' });
                } finally {
                    this.modalSimulasi.saving = false;
                }
            },

            async deleteSimulasi(siswa) {
                const confirm = await Swal.fire({
                    title: 'Hapus Pilihan?',
                    text: `Anda akan menghapus pilihan simulasi untuk ${siswa.nama_lengkap}.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Ya, Hapus'
                });
                if (!confirm.isConfirmed) return;

                try {
                    let url = `${_baseUrl}/api/v1/pdss/simulasi/delete`;
                    if (this.currentTenantId) url += `?tenant_id=${this.currentTenantId}`;
                    const res = await axios.post(url, {
                        siswa_id: siswa.siswa_id,
                        tahun_ajaran_id: this.filterAcademicYear,
                        no_simulasi: this.activeNoSimulasi
                    });
                    if (res.data.success) {
                        Swal.fire({ icon: 'success', title: 'Berhasil', text: res.data.message, confirmButtonColor: '#2563eb' });
                        this.fetchSimulasi();
                    }
                } catch (e) {
                    const msg = (e.response && e.response.data && e.response.data.error) || 'Gagal menghapus pilihan.';
                    Swal.fire({ icon: 'error', title: 'Gagal', text: msg, confirmButtonColor: '#2563eb' });
                }
            },

            openModalUploadBukti(siswa) {
                this.modalUploadBukti.siswa = siswa;
                this.modalUploadBukti.file = null;
                this.modalUploadBukti.show = true;
                this.$nextTick(() => {
                    if (this.$refs.buktiFileInput) {
                        this.$refs.buktiFileInput.value = '';
                    }
                });
            },

            handleFileUpload(event) {
                this.modalUploadBukti.file = event.target.files[0];
            },

            async submitUploadBukti() {
                if (!this.modalUploadBukti.file) {
                    Swal.fire({ icon: 'warning', title: 'Pilih File', text: 'Silakan pilih file bukti terlebih dahulu.', confirmButtonColor: '#2563eb' });
                    return;
                }
                this.modalUploadBukti.uploading = true;
                const formData = new FormData();
                formData.append('siswa_id', this.modalUploadBukti.siswa.siswa_id);
                formData.append('tahun_ajaran_id', this.filterAcademicYear);
                formData.append('no_simulasi', 3);
                formData.append('bukti_file', this.modalUploadBukti.file);

                try {
                    let url = `${_baseUrl}/api/v1/pdss/simulasi/upload-bukti`;
                    if (this.currentTenantId) url += `?tenant_id=${this.currentTenantId}`;
                    const res = await axios.post(url, formData, {
                        headers: { 'Content-Type': 'multipart/form-data' }
                    });
                    if (res.data.success) {
                        this.modalUploadBukti.show = false;
                        Swal.fire({ icon: 'success', title: 'Berhasil', text: res.data.message, confirmButtonColor: '#2563eb' });
                        this.fetchSimulasi();
                    }
                } catch (e) {
                    const msg = (e.response && e.response.data && e.response.data.error) || 'Gagal mengupload file bukti.';
                    Swal.fire({ icon: 'error', title: 'Gagal', text: msg, confirmButtonColor: '#2563eb' });
                } finally {
                    this.modalUploadBukti.uploading = false;
                }
            },

            exportSimulasi() {
                let url = `${_baseUrl}/api/v1/pdss/simulasi/export?tahun_ajaran_id=${this.filterAcademicYear}&no_simulasi=${this.activeNoSimulasi}`;
                if (this.currentTenantId) url += `&tenant_id=${this.currentTenantId}`;
                window.location.href = url;
            }
        }
    });
}

// Super Admin tenant filter
<?php if ($userRole === 'super_admin'): ?>
(function() {
    let btn = document.getElementById('btn-apply-tenant'); if(btn) btn.addEventListener('click', function() {
        const tid = (document.getElementById('sa-tenant-select') ? document.getElementById('sa-tenant-select').value : null) || '';
        const url = new URL(window.location.href);
        if (tid) { url.searchParams.set('tenant_id', tid); }
        else { url.searchParams.delete('tenant_id'); }
        window.location.href = url.toString();
    });
})();
<?php endif; ?>
</script>
