<?php
/**
 * View: PDSS & Alumni Career Tracking Module Dashboard
 * Stack: Vue 3 + Tailwind CSS (preflight OFF to avoid conflict with Bootstrap)
 */
$userRole   = $data['user_role']   ?? ($_SESSION['role_name']    ?? '');
$tenantId   = $data['tenant_id']   ?? '';
$tenantList = $data['tenant_list'] ?? [];
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
<?php if ($userRole === 'super_admin' && empty($is_sub_module)): ?>
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

    <?php if (empty($is_sub_module)): ?>
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
            <div class="card border-0 shadow-sm rounded-2xl mb-4 bg-white border border-slate-100">
                <div class="card-body p-4 flex flex-wrap items-center justify-between gap-4">
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
                            <option value="" disabled>— Pilih Tahun Ajaran —</option>
                            <option v-for="yr in academicYears" :key="yr.id" :value="yr.id">
                                {{ yr.tahun_ajaran }} <span v-if="parseInt(yr.is_active) === 1">(Aktif)</span>
                            </option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- STATUS LOCK DATA KESIAPAN (PER LANGKAH) -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <!-- Lock Langkah 1 -->
                <div class="bg-white rounded-2xl shadow-sm border p-4 flex flex-col justify-between gap-3"
                     :class="locks[1].is_locked ? 'border-amber-200 bg-amber-50/10' : 'border-slate-100'">
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center text-sm flex-shrink-0"
                             :class="locks[1].is_locked ? 'bg-amber-100 text-amber-600' : 'bg-emerald-100 text-emerald-600'">
                            <i class="bi" :class="locks[1].is_locked ? 'bi-lock-fill' : 'bi-unlock-fill'"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-xs text-slate-800 uppercase tracking-wider mb-0.5">Langkah 1: Mapel</h4>
                            <span class="text-[10px] font-bold px-1.5 py-0.5 rounded" :class="locks[1].is_locked ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800'">
                                {{ locks[1].is_locked ? 'TERKUNCI' : 'TERBUKA' }}
                            </span>
                            <p class="text-[10px] text-slate-500 mt-1 mb-0" v-if="locks[1].is_locked">
                                Dikunci oleh {{ locks[1].locked_by }} pada {{ locks[1].locked_at }}.
                            </p>
                        </div>
                    </div>
                    <div v-if="canWrite" class="text-end">
                        <button class="btn btn-xs btn-outline-secondary text-[10px] px-2.5 py-1 rounded-lg"
                                :class="locks[1].is_locked ? 'border-amber-500 text-amber-600 hover:bg-amber-50' : 'border-slate-200'"
                                @click="togglePdssLock(1)">
                            <i class="bi" :class="locks[1].is_locked ? 'bi-unlock-fill' : 'bi-lock-fill'"></i>
                            {{ locks[1].is_locked ? 'Buka Kunci' : 'Kunci' }}
                        </button>
                    </div>
                </div>

                <!-- Lock Langkah 2 -->
                <div class="bg-white rounded-2xl shadow-sm border p-4 flex flex-col justify-between gap-3"
                     :class="locks[2].is_locked ? 'border-amber-200 bg-amber-50/10' : 'border-slate-100'">
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center text-sm flex-shrink-0"
                             :class="locks[2].is_locked ? 'bg-amber-100 text-amber-600' : 'bg-emerald-100 text-emerald-600'">
                            <i class="bi" :class="locks[2].is_locked ? 'bi-lock-fill' : 'bi-unlock-fill'"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-xs text-slate-800 uppercase tracking-wider mb-0.5">Langkah 2: Kuota</h4>
                            <span class="text-[10px] font-bold px-1.5 py-0.5 rounded" :class="locks[2].is_locked ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800'">
                                {{ locks[2].is_locked ? 'TERKUNCI' : 'TERBUKA' }}
                            </span>
                            <p class="text-[10px] text-slate-500 mt-1 mb-0" v-if="locks[2].is_locked">
                                Dikunci oleh {{ locks[2].locked_by }} pada {{ locks[2].locked_at }}.
                            </p>
                        </div>
                    </div>
                    <div v-if="canWrite" class="text-end">
                        <button class="btn btn-xs btn-outline-secondary text-[10px] px-2.5 py-1 rounded-lg"
                                :class="locks[2].is_locked ? 'border-amber-500 text-amber-600 hover:bg-amber-50' : 'border-slate-200'"
                                @click="togglePdssLock(2)">
                            <i class="bi" :class="locks[2].is_locked ? 'bi-unlock-fill' : 'bi-lock-fill'"></i>
                            {{ locks[2].is_locked ? 'Buka Kunci' : 'Kunci' }}
                        </button>
                    </div>
                </div>

                <!-- Lock Langkah 3 -->
                <div class="bg-white rounded-2xl shadow-sm border p-4 flex flex-col justify-between gap-3"
                     :class="locks[3].is_locked ? 'border-amber-200 bg-amber-50/10' : 'border-slate-100'">
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center text-sm flex-shrink-0"
                             :class="locks[3].is_locked ? 'bg-amber-100 text-amber-600' : 'bg-emerald-100 text-emerald-600'">
                            <i class="bi" :class="locks[3].is_locked ? 'bi-lock-fill' : 'bi-unlock-fill'"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-xs text-slate-800 uppercase tracking-wider mb-0.5">Langkah 3: Kelayakan</h4>
                            <span class="text-[10px] font-bold px-1.5 py-0.5 rounded" :class="locks[3].is_locked ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800'">
                                {{ locks[3].is_locked ? 'TERKUNCI' : 'TERBUKA' }}
                            </span>
                            <p class="text-[10px] text-slate-500 mt-1 mb-0" v-if="locks[3].is_locked">
                                Dikunci oleh {{ locks[3].locked_by }} pada {{ locks[3].locked_at }}.
                            </p>
                        </div>
                    </div>
                    <div v-if="canWrite" class="text-end">
                        <button class="btn btn-xs btn-outline-secondary text-[10px] px-2.5 py-1 rounded-lg"
                                :class="locks[3].is_locked ? 'border-amber-500 text-amber-600 hover:bg-amber-50' : 'border-slate-200'"
                                @click="togglePdssLock(3)">
                            <i class="bi" :class="locks[3].is_locked ? 'bi-unlock-fill' : 'bi-lock-fill'"></i>
                            {{ locks[3].is_locked ? 'Buka Kunci' : 'Kunci' }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- KPI Row -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5 flex items-center justify-between">
                <div>
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Siswa Kelas 12</span>
                    <span class="text-2xl font-bold text-slate-800 mt-1 block">{{ stats.totalStudents }}</span>
                    <span class="text-xs text-slate-500 mt-1 block">Aktif dalam database</span>
                </div>
                <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                    <i class="bi bi-people-fill text-xl"></i>
                </div>
            </div>
            
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5 flex items-center justify-between">
                <div>
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Kelengkapan Rapor</span>
                    <span class="text-2xl font-bold text-slate-800 mt-1 block">{{ stats.completenessRate }}%</span>
                    <span class="text-xs text-slate-500 mt-1 block">{{ stats.studentsWithGrades }} siswa terisi nilai</span>
                </div>
                <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <i class="bi bi-file-earmark-check-fill text-xl"></i>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5 flex items-center justify-between">
                <div>
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Siswa Eligible SNBP</span>
                    <span class="text-2xl font-bold text-slate-800 mt-1 block">{{ stats.eligibleCount }}</span>
                    <span class="text-xs text-slate-500 mt-1 block">Berdasarkan simulasi kuota {{ quotaPercent }}%</span>
                </div>
                <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                    <i class="bi bi-award-fill text-xl"></i>
                </div>
            </div>
        </div>

        <!-- ALUR KERJA BK KESIAPAN PDSS (Workflow Cards) -->
        <div class="grid grid-cols-1 gap-6">
            
            <!-- LANGKAH 1: KONFIGURASI MATA PELAJARAN PDSS -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                <div class="px-6 py-4 bg-slate-50/50 border-b border-slate-100 flex items-center justify-between cursor-pointer" @click="showMapelConfig = !showMapelConfig">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-sm">1</div>
                        <div>
                            <h4 class="font-bold text-sm text-slate-800">Langkah 1: Tentukan Mata Pelajaran untuk PDSS</h4>
                            <p class="text-xs text-slate-500">Pilih mata pelajaran dari kurikulum yang akan dihitung nilainya selama 5 semester.</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <span v-if="locks[1].is_locked" class="bg-amber-100 text-amber-800 text-[10px] font-bold py-1 px-2 rounded-lg flex items-center gap-1 select-none">
                            <i class="bi bi-lock-fill"></i> TERKUNCI
                        </span>
                        <i class="bi" :class="showMapelConfig ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
                    </div>
                </div>
                
                <div v-show="showMapelConfig" class="p-6 border-t border-slate-100 animate-fade-in">
                    <!-- Checkbox Grid -->
                    <div v-if="loadingMapels" class="text-center py-4 text-slate-400 text-xs">
                        <div class="spinner-border spinner-border-sm text-primary me-2"></div>
                        Memuat daftar mata pelajaran...
                    </div>
                    <div v-else-if="pdssMapels.length === 0" class="text-center py-4 text-slate-400 text-xs">
                        Belum ada mata pelajaran terdaftar di sekolah ini. Silakan tambahkan di menu Buku Induk.
                    </div>
                    <div v-else>
                        <!-- Table Grid Semester Mapel (Horizontal Scrollable) -->
                        <div class="table-responsive mb-4 rounded-xl border border-slate-100 overflow-hidden">
                            <table class="table table-bordered align-middle text-xs mb-0" style="min-width: 800px;">
                                <thead class="bg-slate-50 text-center font-bold text-slate-600">
                                    <tr>
                                        <th rowspan="2" class="align-middle text-start ps-4" style="min-width: 200px;">Nama Mata Pelajaran</th>
                                        <th colspan="2" class="py-2 border-l">Kelas X (Tahun I)</th>
                                        <th colspan="2" class="py-2 border-l">Kelas XI (Tahun II)</th>
                                        <th colspan="2" class="py-2 border-l">Kelas XII (Tahun III)</th>
                                    </tr>
                                    <tr class="bg-slate-100 text-[10px]">
                                        <th class="py-1 border-l" style="width: 90px;">Semester 1</th>
                                        <th class="py-1" style="width: 90px;">Semester 2</th>
                                        <th class="py-1 border-l" style="width: 90px;">Semester 3</th>
                                        <th class="py-1" style="width: 90px;">Semester 4</th>
                                        <th class="py-1 border-l" style="width: 90px;">Semester 5</th>
                                        <th class="py-1" style="width: 90px;">Semester 6</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="mapel in pdssMapels" :key="mapel.id">
                                        <td class="font-semibold text-slate-700 ps-4">
                                            {{ mapel.nama_mapel }} 
                                            <span class="text-[10px] text-slate-400 font-mono">({{ mapel.kode_mapel }})</span>
                                        </td>
                                        <!-- Sem 1-6 Checkboxes (Conditional on grades existence) -->
                                        <td class="text-center py-2 border-l">
                                            <input v-if="mapel.has_sem_1" type="checkbox" v-model="mapel.sem_1" :disabled="locks[1].is_locked" class="rounded text-indigo-600 focus:ring-indigo-500 w-4 h-4">
                                            <span v-else class="text-slate-300 font-bold">—</span>
                                        </td>
                                        <td class="text-center py-2">
                                            <input v-if="mapel.has_sem_2" type="checkbox" v-model="mapel.sem_2" :disabled="locks[1].is_locked" class="rounded text-indigo-600 focus:ring-indigo-500 w-4 h-4">
                                            <span v-else class="text-slate-300 font-bold">—</span>
                                        </td>
                                        <td class="text-center py-2 border-l">
                                            <input v-if="mapel.has_sem_3" type="checkbox" v-model="mapel.sem_3" :disabled="locks[1].is_locked" class="rounded text-indigo-600 focus:ring-indigo-500 w-4 h-4">
                                            <span v-else class="text-slate-300 font-bold">—</span>
                                        </td>
                                        <td class="text-center py-2">
                                            <input v-if="mapel.has_sem_4" type="checkbox" v-model="mapel.sem_4" :disabled="locks[1].is_locked" class="rounded text-indigo-600 focus:ring-indigo-500 w-4 h-4">
                                            <span v-else class="text-slate-300 font-bold">—</span>
                                        </td>
                                        <td class="text-center py-2 border-l">
                                            <input v-if="mapel.has_sem_5" type="checkbox" v-model="mapel.sem_5" :disabled="locks[1].is_locked" class="rounded text-indigo-600 focus:ring-indigo-500 w-4 h-4">
                                            <span v-else class="text-slate-300 font-bold">—</span>
                                        </td>
                                        <td class="text-center py-2">
                                            <input v-if="mapel.has_sem_6" type="checkbox" v-model="mapel.sem_6" :disabled="locks[1].is_locked" class="rounded text-indigo-600 focus:ring-indigo-500 w-4 h-4">
                                            <span v-else class="text-slate-300 font-bold">—</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="flex justify-end gap-2 border-t pt-4">
                            <button class="btn btn-sm btn-light border font-semibold px-4 py-2 rounded-xl text-xs" @click="showMapelConfig = false">
                                Tutup
                            </button>
                            <button class="btn btn-sm btn-primary font-semibold px-4 py-2 rounded-xl text-xs flex items-center gap-1.5" :disabled="savingMapels || locks[1].is_locked" @click="savePdssMapels">
                                <span v-if="savingMapels" class="spinner-border spinner-border-sm me-1"></span>
                                <i class="bi bi-save"></i> Simpan Pilihan Mapel
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- LANGKAH 2: SIMULASI KUOTA & FILTER RANKING PARALEL -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                <div class="px-6 py-4 bg-slate-50/50 border-b border-slate-100 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold text-sm">2</div>
                        <div>
                            <h4 class="font-bold text-sm text-slate-800">Langkah 2: Atur Kuota Eligible & Filter Nilai Rata-rata 5 Semester</h4>
                            <p class="text-xs text-slate-500">Sesuaikan persentase kuota berdasarkan akreditasi sekolah dan filter siswa berdasarkan kelas/jurusan.</p>
                        </div>
                    </div>
                    <span v-if="locks[2].is_locked" class="bg-amber-100 text-amber-800 text-[10px] font-bold py-1 px-2 rounded-lg flex items-center gap-1 select-none">
                        <i class="bi bi-lock-fill"></i> TERKUNCI
                    </span>
                </div>
                
                <div class="p-6 border-t border-slate-100 space-y-4">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <!-- Accreditation Info -->
                        <div class="bg-slate-50 rounded-xl px-4 py-2 border border-slate-200 flex items-center gap-3">
                            <span class="text-xs text-slate-500">Akreditasi Sekolah:</span>
                            <span class="badge bg-blue-600 text-white font-bold py-1 px-2.5 rounded-lg text-xs">{{ accreditation }}</span>
                            <span class="text-xs text-slate-400 border-l pl-2 font-medium" v-if="accreditation.includes('A')">Kuota Rekomendasi: 40%</span>
                            <span class="text-xs text-slate-400 border-l pl-2 font-medium" v-else-if="accreditation.includes('B')">Kuota Rekomendasi: 25%</span>
                            <span class="text-xs text-slate-400 border-l pl-2 font-medium" v-else>Kuota Rekomendasi: 5%</span>
                        </div>

                        <!-- Quota Selector -->
                        <div class="flex items-center gap-3 flex-wrap">
                            <div class="flex items-center gap-2">
                                <label for="quota-select" class="text-xs font-semibold text-slate-600">Kuota SNBP:</label>
                                <select id="quota-select" v-model="quotaPercent" :disabled="locks[2].is_locked" class="rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs text-slate-700 font-semibold focus:outline-none focus:ring-2 focus:ring-blue-500">
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
                                <input type="checkbox" v-model="useERapor" :disabled="locks[2].is_locked" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                Menggunakan e-Rapor (+5% Kuota SNBP)
                            </label>
                        </div>
                    </div>

                    <!-- Live Filters -->
                    <div class="bg-slate-50/50 p-4 rounded-xl border border-slate-100 flex flex-wrap items-center justify-between gap-3">
                        <div class="text-xs font-bold text-slate-700">Filter Pencarian & Kriteria:</div>
                        <div class="flex flex-wrap gap-2 items-center">
                            <!-- Download Leger by Semester Dropdown and Button -->
                            <div class="flex items-center gap-1 bg-white border border-slate-200 rounded-xl px-2.5 py-1.5 text-xs">
                                <span class="text-slate-500 font-medium select-none">Unduh Nilai:</span>
                                <select v-model="downloadSemester" class="border-0 bg-transparent py-0 px-1 text-xs text-slate-700 font-semibold focus:ring-0 focus:outline-none cursor-pointer">
                                    <option :value="1">Semester 1</option>
                                    <option :value="2">Semester 2</option>
                                    <option :value="3">Semester 3</option>
                                    <option :value="4">Semester 4</option>
                                    <option :value="5">Semester 5</option>
                                    <option :value="6">Semester 6</option>
                                </select>
                                <a v-if="!mapelNotConfigured && students.length > 0 && filterAcademicYear" 
                                   :href="baseUrl + '/api/v1/pdss/download-leger?semester=' + downloadSemester + '&tahun_ajaran_id=' + filterAcademicYear + (currentTenantId ? '&tenant_id=' + currentTenantId : '')" 
                                   class="btn btn-sm btn-primary rounded-lg px-2.5 py-1 text-xs font-semibold flex items-center gap-1.5 transition ms-2"
                                   target="_blank">
                                    <i class="bi bi-file-earmark-excel-fill text-xs text-emerald-400"></i> Unduh
                                </a>
                                <button v-else-if="!mapelNotConfigured && students.length > 0"
                                        class="btn btn-sm btn-secondary rounded-lg px-2.5 py-1 text-xs font-semibold flex items-center gap-1.5 transition ms-2 opacity-50"
                                        @click="showSelectTaWarning">
                                    <i class="bi bi-file-earmark-excel-fill text-xs text-slate-400"></i> Unduh
                                </button>
                            </div>

                            <!-- Search Box -->
                            <div class="position-relative">
                                <i class="bi bi-search position-absolute text-slate-400 text-xs" style="left: 12px; top: 50%; transform: translateY(-50%);"></i>
                                <input type="text" v-model="searchStudent" placeholder="Cari nama / NISN..." class="rounded-xl border border-slate-200 pl-8 pr-3 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500 w-44">
                            </div>

                             <!-- Class Filter -->
                            <select v-model="filterClass" class="rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs text-slate-600 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Semua Kelas</option>
                                <option v-for="cls in uniqueClasses" :key="cls" :value="cls">{{ cls }}</option>
                            </select>
                            
                            <!-- Major Filter -->
                            <select v-model="filterMajor" class="rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs text-slate-600 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Semua Jurusan</option>
                                <option v-for="maj in uniqueMajors" :key="maj" :value="maj">{{ maj }}</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- LANGKAH 3: SIMULASI RANKING PARALEL (TABEL HASIL) -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                <div class="px-6 py-4 bg-slate-50/50 border-b border-slate-100 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-sm">3</div>
                        <div>
                            <h4 class="font-bold text-sm text-slate-800">Langkah 3: Tinjau Kelayakan & Ranking Paralel 5 Semester</h4>
                            <p class="text-xs text-slate-500">Siswa yang masuk kuota (Eligible) akan ditandai dengan warna ungu otomatis.</p>
                        </div>
                    </div>
                    <span v-if="locks[3].is_locked" class="bg-amber-100 text-amber-800 text-[10px] font-bold py-1 px-2 rounded-lg flex items-center gap-1 select-none">
                        <i class="bi bi-lock-fill"></i> TERKUNCI
                    </span>
                </div>

                <!-- Warning if mapel not configured -->
                <div v-if="mapelNotConfigured" class="p-8 text-center">
                    <div class="w-16 h-16 bg-amber-50 text-amber-500 rounded-full flex items-center justify-center mx-auto mb-3">
                        <i class="bi bi-exclamation-triangle-fill text-2xl"></i>
                    </div>
                    <h5 class="font-bold text-slate-800 text-sm">Mata Pelajaran PDSS Belum Dikonfigurasi</h5>
                    <p class="text-xs text-slate-500 max-w-sm mx-auto mt-1 mb-4">Silakan klik dan atur mata pelajaran kurikulum yang digunakan untuk PDSS pada Langkah 1 terlebih dahulu.</p>
                    <button class="btn btn-sm btn-primary font-semibold px-4 py-2 rounded-xl text-xs" @click="showMapelConfig = true">
                        Buka Langkah 1
                    </button>
                </div>

                <!-- Table content -->
                <div v-else class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-100 text-xs text-slate-400 font-semibold uppercase">
                                <th class="ps-6 py-3 text-center">Rank</th>
                                <th class="py-3">Nama Lengkap</th>
                                <th class="py-3">NISN</th>
                                <th class="py-3">Kelas</th>
                                <th class="py-3">Jurusan</th>
                                <th class="py-3 text-center">Total Nilai</th>
                                <th class="py-3 text-center">Rata-rata Nilai</th>
                                <th class="py-3 text-center">Kelengkapan (5 Sem)</th>
                                <th class="py-3 text-center pe-6">Status Kelayakan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Loading State -->
                            <tr v-if="loading">
                                <td colspan="9" class="text-center py-10 text-slate-400 text-xs">
                                     <div class="spinner-border spinner-border-sm text-primary me-2"></div>
                                     Memuat simulasi data siswa...
                                </td>
                            </tr>
                            <!-- Empty State -->
                            <tr v-else-if="filteredStudents.length === 0">
                                <td colspan="9" class="text-center py-10 text-slate-400 text-xs">
                                     Tidak ada data siswa kelas 12 yang terdeteksi dengan kriteria ini.
                                </td>
                            </tr>
                            <!-- Simulated student list -->
                            <tr v-else v-for="stu in filteredStudents" :key="stu.id" class="text-sm border-b border-slate-100 hover:bg-slate-50">
                                <td class="text-center font-bold ps-6 py-2.5">
                                    <span :class="{'text-blue-600': stu.isEligible, 'text-slate-400': !stu.isEligible}">
                                        #{{ stu.majorRank }}
                                    </span>
                                </td>
                                <td>
                                    <div class="flex items-center gap-2">
                                        <button class="btn btn-link p-0 text-slate-400 hover:text-indigo-600" title="Audit Detail Nilai Rapor" @click="showAuditModal(stu.id)">
                                            <i class="bi bi-eye-fill fs-6"></i>
                                        </button>
                                        <span class="font-semibold text-slate-800">{{ stu.nama_lengkap }}</span>
                                    </div>
                                </td>
                                <td class="font-monospace text-xs text-slate-500">{{ stu.nisn || '—' }}</td>
                                <td class="text-slate-600">{{ stu.nama_kelas || '—' }}</td>
                                <td class="text-slate-600">{{ stu.nama_jurusan || '—' }}</td>
                                <td class="text-center font-bold text-slate-700">
                                    {{ stu.total_nilai > 0 ? stu.total_nilai.toFixed(2) : '—' }}
                                </td>
                                <td class="text-center font-bold" :class="{'text-slate-800': stu.rata_rata > 0, 'text-slate-300': stu.rata_rata === 0}">
                                    {{ stu.rata_rata > 0 ? stu.rata_rata.toFixed(2) : '—' }}
                                </td>
                                <td class="text-center">
                                    <span class="text-xs px-2.5 py-1 rounded-full font-semibold"
                                          :class="stu.jumlah_nilai === totalConfiguredSemesters ? 'bg-emerald-50 text-emerald-700 border border-emerald-100' : 'bg-amber-50 text-amber-700 border border-amber-100'">
                                        {{ stu.jumlah_nilai }} / {{ totalConfiguredSemesters }} Nilai
                                    </span>
                                </td>
                                <td class="text-center pe-6">
                                    <div class="flex flex-column align-items-center gap-1.5">
                                        <!-- Status Badge -->
                                        <span class="text-[10px] px-2.5 py-1 rounded-xl font-bold uppercase block w-100 text-center"
                                              :class="stu.isEligible ? 'badge-eligible' : 'badge-not-eligible'">
                                            {{ stu.isEligible ? 'ELIGIBLE' : 'TIDAK ELIGIBLE' }}
                                            <span v-if="stu.status_eligible !== 'auto'" class="text-[9px] text-slate-400 block font-normal">(BK Manual)</span>
                                        </span>
                                        
                                        <!-- BK Override Actions -->
                                        <div v-if="!locks[3].is_locked && canWrite" class="flex gap-1">
                                            <button v-if="stu.status_eligible !== 'eligible'" class="btn btn-success py-0.5 px-1.5 rounded-lg text-[9px] font-bold uppercase border-0 text-white" title="Paksa Eligible" @click="saveManualEligible(stu.id, 'eligible')">
                                                Eligible
                                            </button>
                                            <button v-if="stu.status_eligible !== 'tidak_eligible'" class="btn btn-danger py-0.5 px-1.5 rounded-lg text-[9px] font-bold uppercase border-0 text-white" title="Paksa Tidak Eligible" @click="saveManualEligible(stu.id, 'tidak_eligible')">
                                                Tidak
                                            </button>
                                            <button v-if="stu.status_eligible !== 'auto'" class="btn btn-secondary py-0.5 px-1.5 rounded-lg text-[9px] font-bold uppercase border-0 text-white" title="Reset ke Auto" @click="saveManualEligible(stu.id, 'auto')">
                                                Auto
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
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex flex-wrap gap-2 items-center">
                    <!-- Search Input -->
                    <div class="position-relative">
                        <i class="bi bi-search position-absolute text-slate-400 text-xs" style="left: 12px; top: 50%; transform: translateY(-50%);"></i>
                        <input type="text" v-model="filterAlumni.search" placeholder="Cari nama alumni..." class="rounded-xl border border-slate-200 pl-8 pr-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500 w-48">
                    </div>
                    <!-- Type Filter -->
                    <select v-model="filterAlumni.type" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-600 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Semua Jenis Kampus</option>
                        <option value="Negeri">Negeri (PTN)</option>
                        <option value="Swasta">Swasta (PTS)</option>
                        <option value="Kedinasan">Kedinasan (PTK)</option>
                    </select>
                    <!-- Track Filter -->
                    <select v-model="filterAlumni.track" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-600 focus:outline-none focus:ring-2 focus:ring-blue-500">
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
                    <select v-model="filterAlumni.year" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-600 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Semua Tahun Masuk</option>
                        <option v-for="yr in uniqueAlumniYears" :key="yr" :value="yr">{{ yr }}</option>
                    </select>
                </div>

                <div class="flex items-center gap-4">
                    <!-- Privacy Toggle -->
                    <div class="flex items-center gap-2 bg-slate-50 border rounded-xl px-3 py-1.5">
                        <label for="privacy-mask" class="text-xs font-semibold text-slate-500 cursor-pointer flex items-center gap-1.5 m-0 select-none">
                            <i class="bi bi-shield-shaded text-indigo-500"></i>
                            Sensor Nama Alumni
                        </label>
                        <input type="checkbox" id="privacy-mask" v-model="privacyMask" :disabled="isStudent" class="rounded text-blue-600 border-slate-300 focus:ring-blue-500 cursor-pointer">
                    </div>

                    <!-- Add Alumni Button -->
                    <button v-if="canWrite" class="btn btn-primary rounded-xl px-4 py-2 text-xs font-semibold flex items-center gap-1.5 shadow-sm"
                            @click="openAlumniModal()">
                        <i class="bi bi-plus-lg"></i>
                        Tambah Alumni
                    </button>
                </div>
            </div>
        </div>

        <!-- Grid Data Grid Table -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-100 text-xs text-slate-400 font-semibold uppercase">
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
        <?php include __DIR__ . '/bk/kampus_config_ui.php'; ?>
    </template>

    <!-- TAB: SIMULASI PEMILIHAN KAMPUS & PRODI -->
    <template v-if="activeTab === 'simulasi'">
        <?php include __DIR__ . '/bk/pdss_simulasi_ui.php'; ?>
    </template>

    <!-- MODAL AUDIT DETAIL NILAI RAPOR -->
    <Teleport to="body">
        <div class="modal fade" id="modalAuditGrades" tabindex="-1" aria-hidden="true" style="z-index: 1060;">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content border-0 rounded-2xl shadow-lg">
                    <!-- Header -->
                    <div class="modal-header border-b border-slate-100 px-6 py-4 flex items-center justify-between">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center">
                                <i class="bi bi-file-earmark-spreadsheet-fill text-sm"></i>
                            </div>
                            <div>
                                <h5 class="modal-title font-bold text-sm text-slate-800">Audit Detail Nilai Rapor</h5>
                                <p class="text-[10px] text-slate-400 mt-0.5">Detail nilai per mapel & semester yang digunakan untuk PDSS</p>
                            </div>
                        </div>
                        <button type="button" class="btn-close text-slate-400 bg-transparent border-0 text-xl font-bold" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    
                    <!-- Body -->
                    <div class="modal-body p-6" v-if="auditGrades">
                        <!-- Student Info Card -->
                        <div class="bg-slate-50 border border-slate-100 rounded-xl p-4 mb-4 grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div>
                                <span class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider block">Nama Lengkap</span>
                                <span class="text-xs font-bold text-slate-800 block mt-0.5">{{ auditGrades.student.nama_lengkap }}</span>
                            </div>
                            <div>
                                <span class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider block">NISN</span>
                                <span class="text-xs font-semibold text-slate-700 block mt-0.5 font-monospace">{{ auditGrades.student.nisn || '—' }}</span>
                            </div>
                            <div>
                                <span class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider block">Kelas</span>
                                <span class="text-xs font-semibold text-slate-700 block mt-0.5">{{ auditGrades.student.nama_kelas || '—' }}</span>
                            </div>
                            <div>
                                <span class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider block">Jurusan</span>
                                <span class="text-xs font-semibold text-slate-700 block mt-0.5">{{ auditGrades.student.nama_jurusan || '—' }}</span>
                            </div>
                        </div>

                        <!-- Audit Table -->
                        <div class="table-responsive rounded-xl border border-slate-100 overflow-hidden">
                            <table class="table table-bordered align-middle text-xs mb-0">
                                <thead class="bg-slate-50 text-center font-bold text-slate-600">
                                    <tr>
                                        <th rowspan="2" class="align-middle text-start ps-4" style="min-width: 180px;">Nama Mapel</th>
                                        <th colspan="2" class="py-1.5 border-l">Kelas X</th>
                                        <th colspan="2" class="py-1.5 border-l">Kelas XI</th>
                                        <th colspan="2" class="py-1.5 border-l">Kelas XII</th>
                                    </tr>
                                    <tr class="bg-slate-100 text-[10px]">
                                        <th class="py-1 border-l">Sem 1</th>
                                        <th class="py-1">Sem 2</th>
                                        <th class="py-1 border-l">Sem 3</th>
                                        <th class="py-1">Sem 4</th>
                                        <th class="py-1 border-l">Sem 5</th>
                                        <th class="py-1">Sem 6</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="g in auditGrades.grades" :key="g.mapel_id">
                                        <td class="font-semibold text-slate-700 ps-4">
                                            {{ g.nama_mapel }}
                                            <span class="text-[10px] text-slate-400 font-mono block">({{ g.kode_mapel }})</span>
                                        </td>
                                        <!-- Sem 1-6 -->
                                        <td v-for="sem in [1,2,3,4,5,6]" :key="sem" class="text-center py-2.5">
                                            <div v-if="g['sem_' + sem].is_configured">
                                                <span class="font-bold text-slate-800 animate-fade-in" v-if="g['sem_' + sem].nilai !== null">
                                                    {{ g['sem_' + sem].nilai }}
                                                </span>
                                                <span class="text-slate-300" v-else>—</span>
                                                <span class="text-[9px] text-slate-400 block font-normal mt-0.5" v-if="g['sem_' + sem].tahun_ajaran">
                                                    {{ g['sem_' + sem].tahun_ajaran }}
                                                </span>
                                            </div>
                                            <div v-else class="bg-slate-50 text-[10px] text-slate-400 py-1 select-none font-medium">
                                                N/A
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-body p-6 text-center text-slate-400 text-xs py-10" v-else>
                        <div class="spinner-border spinner-border-sm text-primary me-2"></div>
                        Memuat data nilai audit rapor...
                    </div>
                </div>
            </div>
        </div>
    </Teleport>

</div>

<!-- Vue 3 Interactive Routing & Lifecycle Scripts -->
<script>
{
    const _baseUrl = '/SINTA-SaaS';
    const _userRole = <?= json_encode($data['user_role'] ?? 'siswa', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const _canWrite = <?= json_encode($data['can_write'] ?? false, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const _currentTenantId = <?= json_encode($tenantId, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

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
                pdssMapels: [],
                loadingMapels: false,
                savingMapels: false,
                showMapelConfig: false,
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
                    3: { is_locked: false, locked_by: null, locked_at: null }
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
                }
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
                if (newVal === 'master_kampus') {
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
                // 1. Kelompokkan siswa berdasarkan major
                const groups = {};
                this.students.forEach(s => {
                    if (!groups[s.id_jurusan]) {
                        groups[s.id_jurusan] = [];
                    }
                    groups[s.id_jurusan].push({...s});
                });

                const allProcessed = [];

                // 2. Beri ranking paralel dalam internal masing-masing jurusan & kalkulasi kelayakan kuota
                Object.keys(groups).forEach(jurusanId => {
                    const group = groups[jurusanId];
                    // Urutkan berdasarkan rata_rata nilai DESC, nama ASC
                    group.sort((a, b) => {
                        if (b.rata_rata !== a.rata_rata) {
                            return b.rata_rata - a.rata_rata;
                        }
                        return a.nama_lengkap.localeCompare(b.nama_lengkap);
                    });

                    const N = group.length;
                    // Rumus kuota paralel SNBP nasional: batas = ceil(N * Quota / 100)
                    const limit = Math.max(1, Math.ceil(N * this.quotaPercent / 100));

                    group.forEach((stu, index) => {
                        stu.majorRank = index + 1;
                        if (stu.status_eligible === 'eligible') {
                            stu.isEligible = true;
                        } else if (stu.status_eligible === 'tidak_eligible') {
                            stu.isEligible = false;
                        } else {
                            // Siswa dinyatakan eligible jika masuk kuota dan memiliki nilai rapor terinput (> 0)
                            stu.isEligible = (stu.majorRank <= limit) && (stu.rata_rata > 0);
                        }
                        allProcessed.push(stu);
                    });
                });

                // Urutkan kembali berdasarkan nama jurusan & rank internal untuk tampilan
                allProcessed.sort((a, b) => {
                    if (a.nama_jurusan !== b.nama_jurusan) {
                        return a.nama_jurusan.localeCompare(b.nama_jurusan);
                    }
                    return a.majorRank - b.majorRank;
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
                const eligibleCount = list.filter(s => s.isEligible).length;

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
            this.refreshAll();
            // Siswa secara paksa tidak bisa mematikan masking
            if (this.isStudent) {
                this.privacyMask = true;
            }
            if (this.activeTab === 'master_kampus') {
                this.fetchKampus();
            } else if (this.activeTab === 'master_jalur') {
                this.fetchJalur();
            }
        },

        methods: {
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
                    await Promise.all([
                        this.fetchKesiapan(),
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
                if (this.userRole === 'super_admin' && !this.currentTenantId) {
                    this.students = [];
                    return;
                }
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
                            const active = this.academicYears.find(y => parseInt(y.is_active) === 1);
                            if (active) this.filterAcademicYear = active.id;
                            else this.filterAcademicYear = this.academicYears[0].id;
                        }

                        this.locks = res.data.locks || {
                            1: { is_locked: false, locked_by: null, locked_at: null },
                            2: { is_locked: false, locked_by: null, locked_at: null },
                            3: { is_locked: false, locked_by: null, locked_at: null }
                        };

                        // Auto-set default quota percentage based on accreditation (draft/unlocked only)
                        if (!this.locks[2].is_locked) {
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
                if (this.userRole === 'super_admin' && !this.currentTenantId) {
                    this.pdssMapels = [];
                    return;
                }
                this.loadingMapels = true;
                try {
                    let url = this.currentTenantId ? `${_baseUrl}/api/v1/pdss/config-mapel?tenant_id=${this.currentTenantId}` : `${_baseUrl}/api/v1/pdss/config-mapel`;
                    if (this.filterAcademicYear) {
                        url += (url.includes('?') ? '&' : '?') + 'tahun_ajaran_id=' + encodeURIComponent(this.filterAcademicYear);
                    }
                    const res = await axios.get(url);
                    if (res.data.success) {
                        this.pdssMapels = res.data.data || [];
                    }
                } catch (e) {
                    console.error('Failed fetching PDSS mapels', e);
                } finally {
                    this.loadingMapels = false;
                }
            },

            async savePdssMapels() {
                if (this.locks[1].is_locked) return;
                const configs = this.pdssMapels.map(m => ({
                    mapel_id: m.id,
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
                    const res = await axios.post(url, { configs, tahun_ajaran_id: this.filterAcademicYear });
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
                if (this.locks[3].is_locked) return;
                try {
                    const url = this.currentTenantId ? `${_baseUrl}/api/v1/pdss/manual-eligible?tenant_id=${this.currentTenantId}` : `${_baseUrl}/api/v1/pdss/manual-eligible`;
                    const res = await axios.post(url, { siswa_id: siswaId, status_eligible: status });
                    if (res.data.success) {
                        this.fetchKesiapan();
                    }
                } catch (e) {
                    console.error('Failed updating manual eligibility', e);
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: e.response?.data?.error || 'Gagal merubah status kelayakan.'
                    });
                }
            },

            async togglePdssLock(step) {
                const targetLockState = !this.locks[step].is_locked;
                const actionText = targetLockState ? 'mengunci' : 'membuka kunci';
                const confirmText = targetLockState 
                    ? `Langkah ${step} akan dibekukan.` 
                    : `Anda akan dapat mengubah Langkah ${step} kembali.`;

                const result = await Swal.fire({
                    title: `Apakah Anda yakin ingin ${actionText} data Langkah ${step}?`,
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
                            text: `Status penguncian Langkah ${step} berhasil diperbarui.`
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
                const myModal = new bootstrap.Modal(document.getElementById('modalAuditGrades'));
                myModal.show();
                
                try {
                    const url = this.currentTenantId 
                        ? `${_baseUrl}/api/v1/pdss/student-grades?siswa_id=${siswaId}&tenant_id=${this.currentTenantId}` 
                        : `${_baseUrl}/api/v1/pdss/student-grades?siswa_id=${siswaId}`;
                    const res = await axios.get(url);
                    if (res.data.success) {
                        this.auditGrades = res.data;
                    }
                } catch (e) {
                    console.error('Failed fetching audit grades', e);
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: 'Gagal memuat rincian nilai siswa.'
                    });
                    myModal.hide();
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
                    if (res.data.success) {
                        Swal.fire({ icon: 'success', title: 'Berhasil', text: res.data.message, confirmButtonColor: '#2563eb' });
                        this.fetchSimulasiSettings();
                        this.fetchSimulasi();
                    }
                } catch (e) {
                    const msg = (e.response && e.response.data && e.response.data.error) || 'Gagal mengubah status simulasi.';
                    Swal.fire({ icon: 'error', title: 'Gagal', text: msg, confirmButtonColor: '#2563eb' });
                }
            },

            async fetchSimulasi() {
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
                this.$refs.buktiFileInput.value = '';
                this.modalUploadBukti.show = true;
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
