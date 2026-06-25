<?php
/**
 * View: PDSS & Alumni Career Tracking Module Dashboard
 * Stack: Vue 3 + Tailwind CSS (preflight OFF to avoid conflict with Bootstrap)
 */
$userRole   = $data['user_role']   ?? ($_SESSION['role_name']    ?? '');
$tenantId   = $data['tenant_id']   ?? '';
$tenantList = $data['tenant_list'] ?? [];
?>

<!-- Disable Tailwind Preflight & Load Play CDN -->
<script>
(function() {
    const origWarn = console.warn;
    console.warn = function(...args) {
        if (typeof args[0] === 'string' && args[0].includes('cdn.tailwindcss.com')) return;
        origWarn.apply(console, args);
    };
})();
window.tailwind = { config: { corePlugins: { preflight: false } } };
</script>
<script src="/SINTA-SaaS/assets/js/tailwindcss.js"></script>

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
</style>

<!-- Super Admin: Pilih Sekolah Terlebih Dahulu -->
<?php if ($userRole === 'super_admin'): ?>
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

    <!-- TABS NAVIGATION -->
    <div class="border-b border-slate-200 mb-6">
        <ul class="flex flex-wrap -mb-px text-sm font-medium text-center gap-2">
            <li>
                <button @click="activeTab = 'kesiapan'" 
                        class="inline-flex items-center gap-2 px-4 py-2.5 border-b-2 border-transparent bg-transparent font-semibold text-slate-500 hover:text-slate-700 transition"
                        :class="{'tab-active': activeTab === 'kesiapan'}">
                    <i class="bi bi-award-fill"></i> Kesiapan & Eligibilitas Siswa
                </button>
            </li>
            <li>
                <button @click="activeTab = 'tracking'" 
                        class="inline-flex items-center gap-2 px-4 py-2.5 border-b-2 border-transparent bg-transparent font-semibold text-slate-500 hover:text-slate-700 transition"
                        :class="{'tab-active': activeTab === 'tracking'}">
                    <i class="bi bi-mortarboard-fill"></i> Tracking Alumni & Rekam Kampus
                </button>
            </li>
            <li>
                <button @click="activeTab = 'config'" 
                        class="inline-flex items-center gap-2 px-4 py-2.5 border-b-2 border-transparent bg-transparent font-semibold text-slate-500 hover:text-slate-700 transition"
                        :class="{'tab-active': activeTab === 'config'}">
                    <i class="bi bi-gear-fill"></i> Konfigurasi Target Kampus
                </button>
            </li>
        </ul>
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

        <!-- Simulation Settings Bar -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center flex-shrink-0">
                        <i class="bi bi-sliders"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-sm text-slate-800">Simulasi Quota SNBP Sekolah</h4>
                        <p class="text-xs text-slate-500">Sesuaikan kuota paralel berdasarkan akreditasi sekolah saat ini.</p>
                    </div>
                </div>

                <!-- Accreditation Alert Badge -->
                <div class="bg-slate-50 rounded-xl px-4 py-2 border border-slate-200 flex items-center gap-3">
                    <span class="text-xs text-slate-500">Akreditasi:</span>
                    <span class="badge bg-blue-600 text-white font-bold py-1 px-2.5 rounded-lg text-xs">{{ accreditation }}</span>
                    <span class="text-xs text-slate-400 border-l pl-2 font-medium" v-if="accreditation.includes('A')">Rekomendasi Quota: 40%</span>
                    <span class="text-xs text-slate-400 border-l pl-2 font-medium" v-else-if="accreditation.includes('B')">Rekomendasi Quota: 25%</span>
                    <span class="text-xs text-slate-400 border-l pl-2 font-medium" v-else>Rekomendasi Quota: 5%</span>
                </div>

                <!-- Custom Quota Selector -->
                <div class="flex items-center gap-2">
                    <label for="quota-select" class="text-xs font-semibold text-slate-600">Kuota paralel:</label>
                    <select id="quota-select" v-model="quotaPercent" class="rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs text-slate-700 font-semibold focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option :value="40">40% (Akreditasi A)</option>
                        <option :value="25">25% (Akreditasi B)</option>
                        <option :value="5">5% (Akreditasi C)</option>
                        <option :value="10">10%</option>
                        <option :value="15">15%</option>
                        <option :value="30">30%</option>
                        <option :value="50">50%</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Student Listing & Simulated Ranking -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex flex-wrap items-center justify-between gap-3">
                <div class="font-bold text-slate-800 text-base">
                    Simulasi Pemetaan Kelayakan
                </div>
                <div class="flex gap-2 items-center">
                    <!-- Search Input -->
                    <div class="position-relative">
                        <i class="bi bi-search position-absolute text-slate-400 text-xs" style="left: 12px; top: 50%; transform: translateY(-50%);"></i>
                        <input type="text" v-model="searchStudent" placeholder="Cari nama / NISN..." class="rounded-xl border border-slate-200 pl-8 pr-3 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500 w-44">
                    </div>
                    <!-- Major Filter -->
                    <select v-model="filterMajor" class="rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs text-slate-600 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Semua Jurusan</option>
                        <option v-for="maj in uniqueMajors" :key="maj" :value="maj">{{ maj }}</option>
                    </select>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-100 text-xs text-slate-400 font-semibold uppercase">
                            <th class="ps-6 py-3 text-center">Rank</th>
                            <th class="py-3">Nama Lengkap</th>
                            <th class="py-3">NISN</th>
                            <th class="py-3">Kelas</th>
                            <th class="py-3">Jurusan</th>
                            <th class="py-3 text-center">Rata-rata Nilai</th>
                            <th class="py-3 text-center">Kelengkapan</th>
                            <th class="py-3 text-center pe-6">Status Kelayakan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Loading State -->
                        <tr v-if="loading">
                            <td colspan="8" class="text-center py-10 text-slate-400 text-xs">
                                <div class="spinner-border spinner-border-sm text-primary me-2"></div>
                                Memuat simulasi data siswa...
                            </td>
                        </tr>
                        <!-- Empty State -->
                        <tr v-else-if="filteredStudents.length === 0">
                            <td colspan="8" class="text-center py-10 text-slate-400 text-xs">
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
                            <td class="font-semibold text-slate-800">{{ stu.nama_lengkap }}</td>
                            <td class="font-monospace text-xs text-slate-500">{{ stu.nisn || '—' }}</td>
                            <td class="text-slate-600">{{ stu.nama_kelas || '—' }}</td>
                            <td class="text-slate-600">{{ stu.nama_jurusan || '—' }}</td>
                            <td class="text-center font-bold" :class="{'text-slate-800': stu.rata_rata > 0, 'text-slate-300': stu.rata_rata === 0}">
                                {{ stu.rata_rata > 0 ? stu.rata_rata.toFixed(2) : '—' }}
                            </td>
                            <td class="text-center">
                                <span class="text-xs px-2.5 py-1 rounded-full font-semibold"
                                      :class="stu.jumlah_nilai > 0 ? 'bg-slate-100 text-slate-600' : 'bg-rose-50 text-rose-600 border border-rose-100'">
                                    {{ stu.jumlah_nilai }} Mata Pelajaran
                                </span>
                            </td>
                            <td class="text-center pe-6">
                                <span class="text-xs px-3 py-1.5 rounded-xl font-bold uppercase"
                                      :class="stu.isEligible ? 'badge-eligible' : 'badge-not-eligible'">
                                    {{ stu.isEligible ? 'ELIGIBLE' : 'TIDAK ELIGIBLE' }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
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
        </div>

        <!-- MODAL ADD/EDIT ALUMNI (INLINE GLASSMORPHISM OVERLAY) -->
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
                                           @focus="showSearchDropdown = true"
                                           @blur="hideSearchDropdownWithDelay"
                                           placeholder="Cari nama / NIS / NISN..."
                                           autocomplete="off"
                                           required 
                                           class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <span class="absolute right-3 top-1/2 -translate-y-1/2 flex items-center" v-if="searchingStudents">
                                        <span class="spinner-border spinner-border-sm text-slate-400" role="status" style="width: 12px; height: 12px;"></span>
                                    </span>
                                </div>
                                
                                <!-- Dropdown Overlay -->
                                <div v-if="showSearchDropdown && searchResults.length > 0" 
                                     class="absolute z-[9999] left-0 right-0 mt-1 bg-white border border-slate-200 rounded-xl shadow-lg max-h-48 overflow-y-auto">
                                    <button type="button" 
                                            v-for="s in searchResults" 
                                            :key="s.id" 
                                            @mousedown.prevent="selectStudent(s)"
                                            class="w-full text-left px-3 py-2 text-xs hover:bg-slate-50 border-0 border-b border-slate-100 last:border-b-0 flex justify-between items-center bg-transparent cursor-pointer">
                                        <div>
                                            <span class="font-bold text-slate-800">{{ s.nama_lengkap }}</span>
                                            <span class="text-slate-400 block text-[10px]" v-if="s.nisn || s.nis">
                                                NISN: {{ s.nisn || '-' }} | NIS: {{ s.nis || '-' }}
                                            </span>
                                        </div>
                                        <span class="badge bg-blue-50 text-blue-600 font-bold px-1.5 py-0.5 rounded text-[9px]">Siswa</span>
                                    </button>
                                </div>
                                <div v-else-if="showSearchDropdown && searchResults.length === 0 && modalAlumni.form.nama_alumni.trim().length >= 2"
                                     class="absolute z-[9999] left-0 right-0 mt-1 bg-white border border-slate-200 rounded-xl shadow-lg p-3 text-center text-xs text-slate-400">
                                    <i class="bi bi-info-circle me-1"></i> Tidak ada siswa cocok. Tekan Enter untuk simpan nama manual.
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
        </template>
    </div>

    <!-- TAB 3: KONFIGURASI TARGET KAMPUS -->
    <div v-show="activeTab === 'config'" class="space-y-6">
        <div v-if="userRole === 'super_admin' && !currentTenantId" class="bg-amber-50 border border-amber-100 rounded-2xl p-8 text-center shadow-sm">
            <div class="w-16 h-16 rounded-full bg-amber-100 text-amber-600 flex items-center justify-center mx-auto mb-4">
                <i class="bi bi-funnel-fill text-2xl"></i>
            </div>
            <h4 class="font-bold text-slate-800 text-base">Pilih Sekolah Terlebih Dahulu</h4>
            <p class="text-slate-500 text-xs mt-1 max-w-sm mx-auto">Silakan pilih sekolah pada filter di bagian atas halaman untuk menampilkan data.</p>
        </div>
        <template v-else>
            <!-- Config Info Header Card -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5 flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center flex-shrink-0">
                    <i class="bi bi-building-lock text-lg"></i>
                </div>
                <div>
                    <h4 class="font-bold text-sm text-slate-800">Master Data Target Kampus & Quota</h4>
                    <p class="text-xs text-slate-500">Isi target penerimaan PTN/PTS/Kedinasan untuk memandu target belajar siswa.</p>
                </div>
            </div>

            <!-- Seed & Add buttons for Admin/BK only -->
            <div v-if="canWrite" class="flex gap-2">
                <!-- Seeder Button -->
                <button class="btn btn-outline-primary border rounded-xl px-4 py-2 text-xs font-semibold flex items-center gap-1.5 hover:bg-blue-50"
                        @click="seedDefaultCampuses" :disabled="loading">
                    <i class="bi bi-cloud-arrow-down"></i>
                    Seed Master Kampus
                </button>
                <!-- Add Button -->
                <button class="btn btn-primary rounded-xl px-4 py-2 text-xs font-semibold flex items-center gap-1.5 shadow-sm"
                        @click="openCampusModal()">
                    <i class="bi bi-plus-lg"></i>
                    Tambah Target
                </button>
            </div>
        </div>

        <!-- Campus Grid Table -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-100 text-xs text-slate-400 font-semibold uppercase">
                            <th class="ps-6 py-3">#</th>
                            <th class="py-3">Nama Kampus / Perguruan Tinggi</th>
                            <th class="py-3 text-center">Jenis Perguruan Tinggi</th>
                            <th class="py-3 text-center">Target Penerimaan (Siswa)</th>
                            <th v-if="canWrite" class="py-3 text-end pe-6">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Loading State -->
                        <tr v-if="loading">
                            <td colspan="5" class="text-center py-10 text-slate-400 text-xs">
                                <div class="spinner-border spinner-border-sm text-primary me-2"></div>
                                Memuat data target kampus...
                            </td>
                        </tr>
                        <!-- Empty State -->
                        <tr v-else-if="campusData.length === 0">
                            <td colspan="5" class="text-center py-10 text-slate-400 text-xs">
                                Belum ada konfigurasi target kampus yang diatur. Silakan klik **Seed Master Kampus** untuk data awal instan.
                            </td>
                        </tr>
                        <!-- Data Rows -->
                        <tr v-else v-for="(cp, idx) in campusData" :key="cp.id" class="text-sm border-b border-slate-100 hover:bg-slate-50">
                            <td class="ps-6 py-2.5 text-slate-400 font-bold">{{ idx + 1 }}</td>
                            <td class="font-bold text-slate-800">{{ cp.nama_kampus }}</td>
                            <td class="text-center">
                                <span class="text-xs font-semibold px-2.5 py-0.5 rounded-lg"
                                      :class="{
                                          'bg-blue-50 text-blue-700 border border-blue-100': cp.jenis_kampus === 'Negeri',
                                          'bg-indigo-50 text-indigo-700 border border-indigo-100': cp.jenis_kampus === 'Swasta',
                                          'bg-amber-50 text-amber-700 border border-amber-100': cp.jenis_kampus === 'Kedinasan'
                                      }">
                                    {{ cp.jenis_kampus }}
                                </span>
                            </td>
                            <td class="text-center font-bold text-slate-700">{{ cp.kuota_target }} Siswa</td>
                            <td v-if="canWrite" class="text-end pe-6">
                                <div class="d-flex justify-content-end gap-1.5">
                                    <button class="btn btn-sm btn-light border px-2 py-1 text-slate-600 hover:bg-slate-100 rounded-lg text-xs" @click="openCampusModal(cp)">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-sm btn-light border px-2 py-1 text-rose-600 hover:bg-rose-50 hover:text-rose-700 rounded-lg text-xs" @click="deleteTargetKampus(cp)">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- MODAL ADD/EDIT TARGET KAMPUS -->
        <div v-if="modalCampus.show" class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
            <div class="bg-white border rounded-2xl shadow-2xl w-full max-w-md overflow-hidden animate-fade-in">
                <!-- Header -->
                <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                    <h3 class="font-bold text-slate-800 text-base mb-0 flex items-center gap-2">
                        <i class="bi" :class="modalCampus.form.id ? 'bi-pencil-square text-indigo-500' : 'bi-plus-circle text-blue-500'"></i>
                        {{ modalCampus.form.id ? 'Edit Target Kampus' : 'Tambah Target Kampus' }}
                    </h3>
                    <button class="text-slate-400 hover:text-slate-600 text-xl font-bold bg-transparent border-0" @click="modalCampus.show = false">&times;</button>
                </div>
                <!-- Body -->
                <form @submit.prevent="saveTargetKampus">
                    <div class="p-5 space-y-3.5 text-left">
                        <div>
                            <label for="cp_name" class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Nama Kampus <span class="text-rose-500">*</span></label>
                            <input type="text" id="cp_name" v-model="modalCampus.form.nama_kampus" placeholder="e.g. Universitas Gadjah Mada (UGM)" required class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label for="cp_type" class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Jenis Perguruan Tinggi <span class="text-rose-500">*</span></label>
                            <select id="cp_type" v-model="modalCampus.form.jenis_kampus" required class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="Negeri">Negeri</option>
                                <option value="Swasta">Swasta</option>
                                <option value="Kedinasan">Kedinasan</option>
                            </select>
                        </div>
                        <div>
                            <label for="cp_quota" class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Target Kuota Penerimaan <span class="text-rose-500">*</span></label>
                            <input type="number" id="cp_quota" v-model.number="modalCampus.form.kuota_target" required min="0" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>
                    <!-- Footer -->
                    <div class="px-5 py-4 border-t border-slate-100 flex items-center justify-end gap-2 bg-slate-50">
                        <button type="button" class="btn btn-light border rounded-xl px-4 py-2 text-xs font-semibold text-slate-600" @click="modalCampus.show = false">Batal</button>
                        <button type="submit" class="btn btn-primary rounded-xl px-4 py-2 text-xs font-semibold flex items-center gap-1">
                            <i class="bi bi-floppy"></i>
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
        </template>
    </div>

</div>

<!-- Vue 3 Interactive Routing & Lifecycle Scripts -->
<script>
{
    const _baseUrl = '/SINTA-SaaS';
    const _userRole = <?= json_encode($data['user_role'] ?? 'siswa') ?>;
    const _canWrite = <?= json_encode($data['can_write'] ?? false) ?>;
    const _currentTenantId = <?= json_encode($tenantId) ?>;

    window.VueAppRegistry.register('#pdssApp', {
        data() {
            return {
                currentTenantId: _currentTenantId,
                userRole: _userRole,
                activeTab: 'kesiapan',
                accreditation: 'A',
                students: [],
                alumniData: [],
                campusData: [],
                loading: false,
                quotaPercent: 40,
                searchStudent: '',
                filterMajor: '',
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
                    }
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
                }
            };
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
                        // Siswa dinyatakan eligible jika masuk kuota dan memiliki nilai rapor terinput (> 0)
                        stu.isEligible = (stu.majorRank <= limit) && (stu.rata_rata > 0);
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
                return list;
            },

            // Cohort Stats
            stats() {
                const totalStudents = this.students.length;
                const studentsWithGrades = this.students.filter(s => s.rata_rata > 0).length;
                const completenessRate = totalStudents > 0 ? Math.round((studentsWithGrades / totalStudents) * 100) : 0;
                
                // Hitung total eligible dari simulasi saat ini
                const eligibleCount = this.processedStudents.filter(s => s.isEligible).length;

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
            this.refreshAll();
            // Siswa secara paksa tidak bisa mematikan masking
            if (this.isStudent) {
                this.privacyMask = true;
            }
        },

        methods: {
            async refreshAll() {
                this.loading = true;
                try {
                    await Promise.all([
                        this.fetchKesiapan(),
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
                    const url = this.currentTenantId ? `${_baseUrl}/api/v1/pdss/kesiapan?tenant_id=${this.currentTenantId}` : `${_baseUrl}/api/v1/pdss/kesiapan`;
                    const res = await axios.get(url);
                    if (res.data.success) {
                        this.students = res.data.data || [];
                        this.accreditation = res.data.accreditation || 'A';
                        // Auto-set default quota percentage based on accreditation
                        if (this.accreditation.includes('A')) this.quotaPercent = 40;
                        else if (this.accreditation.includes('B')) this.quotaPercent = 25;
                        else this.quotaPercent = 5;
                    }
                } catch (e) {
                    console.error('Failed fetching PDSS stats', e);
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
                 }
                 this.modalAlumni.show = true;
             },

             // ─── STUDENT SEARCH FOR ALUMNI ───────────────────────
             async searchStudents() {
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
                    const msg = e.response?.data?.error || 'Gagal menyimpan data alumni.';
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
                    const msg = e.response?.data?.error || 'Gagal menghapus data.';
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
                    const msg = e.response?.data?.error || 'Gagal menyimpan target kampus.';
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
                    const msg = e.response?.data?.error || 'Gagal menghapus data.';
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
                    const msg = e.response?.data?.error || 'Gagal melakukan seeding data target kampus.';
                    Swal.fire({ icon: 'error', title: 'Gagal', text: msg, confirmButtonColor: '#2563eb' });
                } finally {
                    this.loading = false;
                }
            }
        }
    });
}

// Super Admin tenant filter
<?php if ($userRole === 'super_admin'): ?>
(function() {
    document.getElementById('btn-apply-tenant')?.addEventListener('click', function() {
        const tid = document.getElementById('sa-tenant-select')?.value || '';
        const url = new URL(window.location.href);
        if (tid) { url.searchParams.set('tenant_id', tid); }
        else { url.searchParams.delete('tenant_id'); }
        window.location.href = url.toString();
    });
})();
<?php endif; ?>
</script>
