<?php
/**
 * View: Antrean Sistem & Background Jobs
 * Bagian ini dimuat secara dinamis oleh views/layout/master.php di area #main-content.
 */
?>

<!-- Load Tailwind CSS CDN and disable preflight to prevent conflict with Bootstrap core styles -->
<script>
    (function() {
        const origWarn = console.warn;
        console.warn = function(...args) {
            if (typeof args[0] === 'string' && args[0].includes('cdn.tailwindcss.com')) return;
            origWarn.apply(console, args);
        };
    })();

    // Configure Tailwind config BEFORE loading tailwindcss.js to avoid race conditions under Turbo
    window.tailwind = {
        config: {
            corePlugins: {
                preflight: false,
            }
        }
    };
</script>
<script src="/SINTA-SaaS/assets/js/tailwindcss.js"></script>

<style>
    /* Styling khusus monitoring antrean */
    .metric-card {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .metric-card:hover {
        transform: translateY(-2px);
    }
    .badge-status {
        font-size: 0.75rem;
        font-weight: 700;
        letter-spacing: 0.5px;
    }
</style>

<div id="queueMonitoringApp" v-cloak class="font-sans antialiased text-slate-800">

    <!-- Header Section with Modern Purple/Indigo Gradient Banner -->
    <div class="relative bg-gradient-to-r from-violet-700 via-indigo-600 to-blue-700 rounded-3xl p-6 md:p-8 mb-8 text-white shadow-lg overflow-hidden">
        <div class="absolute -right-10 -top-10 w-40 h-40 bg-white/5 rounded-full blur-xl"></div>
        <div class="absolute -left-10 -bottom-10 w-60 h-60 bg-indigo-400/10 rounded-full blur-2xl"></div>
        
        <div class="relative flex flex-col md:flex-row items-center gap-6 justify-between">
            <div class="flex items-center gap-5">
                <div class="w-16 h-16 md:w-20 md:h-20 bg-white/10 backdrop-blur-md rounded-2xl flex items-center justify-center border border-white/20 shadow-inner">
                    <i class="bi bi-cpu text-3xl md:text-4xl text-white"></i>
                </div>
                <div>
                    <h2 class="text-xl md:text-2xl font-bold tracking-tight text-white mb-1">Antrean Sistem & Background Jobs</h2>
                    <p class="text-white/80 text-sm mb-0">Memantau tugas pemrosesan latar belakang secara real-time dan mengelola performa SaaS platform.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Grid Layout: KPIs & Filter Row -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        
        <!-- KPI Card: Pending -->
        <div class="metric-card bg-white rounded-3xl p-5 shadow-sm border border-slate-200/80 border-l-4 border-l-amber-500">
            <div class="flex justify-between items-center">
                <div>
                    <span class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Menunggu (Pending)</span>
                    <h3 class="text-2xl font-bold text-slate-800 font-mono mb-0">{{ metrics.pending }}</h3>
                </div>
                <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-500 flex items-center justify-center">
                    <i class="bi bi-hourglass-split text-lg"></i>
                </div>
            </div>
        </div>

        <!-- KPI Card: Processing -->
        <div class="metric-card bg-white rounded-3xl p-5 shadow-sm border border-slate-200/80 border-l-4 border-l-blue-500">
            <div class="flex justify-between items-center">
                <div>
                    <span class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Diproses (Processing)</span>
                    <h3 class="text-2xl font-bold text-slate-800 font-mono mb-0">
                        {{ metrics.processing }}
                        <span v-if="metrics.processing > 0" class="spinner-border spinner-border-sm text-blue-500 ms-1" role="status"></span>
                    </h3>
                </div>
                <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-500 flex items-center justify-center">
                    <i class="bi bi-gear-wide-connected text-lg" :class="{'animate-spin': metrics.processing > 0}"></i>
                </div>
            </div>
        </div>

        <!-- KPI Card: Completed -->
        <div class="metric-card bg-white rounded-3xl p-5 shadow-sm border border-slate-200/80 border-l-4 border-l-emerald-500">
            <div class="flex justify-between items-center">
                <div>
                    <span class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Selesai (Completed)</span>
                    <h3 class="text-2xl font-bold text-slate-800 font-mono mb-0">{{ metrics.completed }}</h3>
                </div>
                <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-500 flex items-center justify-center">
                    <i class="bi bi-patch-check text-lg"></i>
                </div>
            </div>
        </div>

        <!-- KPI Card: Failed -->
        <div class="metric-card bg-white rounded-3xl p-5 shadow-sm border border-slate-200/80 border-l-4 border-l-red-500">
            <div class="flex justify-between items-center">
                <div>
                    <span class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Gagal (Failed)</span>
                    <h3 class="text-2xl font-bold text-slate-800 font-mono mb-0">{{ metrics.failed }}</h3>
                </div>
                <div class="w-10 h-10 rounded-xl bg-red-50 text-red-500 flex items-center justify-center">
                    <i class="bi bi-exclamation-triangle text-lg"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Action Panels: Manual Worker & Simulation Forms -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
        
        <!-- Local Worker Trigger Panel -->
        <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-200/80 flex flex-col justify-between">
            <div>
                <div class="flex items-center gap-2 mb-3">
                    <i class="bi bi-terminal text-blue-600 text-lg"></i>
                    <h5 class="text-sm font-bold text-slate-800 mb-0">Eksekutor Antrean Manual (Web Runner)</h5>
                </div>
                <p class="text-xs text-slate-500 leading-relaxed mb-4">
                    Jalankan satu tugas terdepan yang berstatus pending langsung dari browser. Gunakan tombol ini untuk memverifikasi pengerjaan antrean secara instan.
                </p>
            </div>
            
            <button @click="runWorkerOnce" :disabled="runningWorker || metrics.pending === 0" 
                    class="w-full h-11 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs rounded-xl shadow-md shadow-blue-500/10 hover:shadow-blue-600/20 transition flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
                <span v-if="runningWorker" class="spinner-border spinner-border-sm" role="status"></span>
                <i v-else class="bi bi-play-circle-fill"></i>
                {{ runningWorker ? 'Memproses Pekerjaan...' : 'Jalankan Satu Pekerjaan' }}
            </button>
        </div>

        <!-- Simulation Dispatch Panel -->
        <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-200/80 lg:col-span-2 flex flex-col justify-between">
            <div>
                <div class="flex items-center gap-2 mb-3">
                    <i class="bi bi-plus-circle text-indigo-600 text-lg"></i>
                    <h5 class="text-sm font-bold text-slate-800 mb-0">Simulasi Penambahan Pekerjaan Antrean</h5>
                </div>
                <p class="text-xs text-slate-500 leading-relaxed mb-4">
                    Masukkan tugas tiruan ke dalam antrean sistem untuk menguji pemrosesan latar belakang.
                </p>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                    <!-- Select Simulation Type -->
                    <div>
                        <label for="simJobType" class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Tipe Simulasi</label>
                        <select id="simJobType" name="sim_job_type" v-model="simJobType" class="w-full h-10 px-3 rounded-lg border border-slate-200 bg-white text-xs font-semibold text-slate-700 focus:outline-none">
                            <option value="DEMO_SYNC_SUCCESS">Sinkronisasi Pusdatin (Sukses)</option>
                            <option value="DEMO_SYNC_FAIL">Sinkronisasi Pusdatin (Simulasi Gagal)</option>
                            <option value="DEMO_EMAIL">Kirim Email Blast Masal</option>
                        </select>
                    </div>

                    <!-- Select School / Tenant (Super Admin Only) -->
                    <?php if ($user_role === 'super_admin'): ?>
                    <div>
                        <label for="simTenantId" class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Sekolah / Tenant</label>
                        <select id="simTenantId" name="sim_tenant_id" v-model="simTenantId" class="w-full h-10 px-3 rounded-lg border border-slate-200 bg-white text-xs font-semibold text-slate-700 focus:outline-none">
                            <option value="">-- Sistem Global --</option>
                            <?php foreach ($tenantsList as $t): ?>
                                <option value="<?= htmlspecialchars($t['id']) ?>"><?= htmlspecialchars($t['nama_sekolah']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <button @click="dispatchSimJob" :disabled="dispatching" 
                    class="w-full h-11 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl shadow-md shadow-indigo-500/10 hover:shadow-indigo-600/20 transition flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50">
                <span v-if="dispatching" class="spinner-border spinner-border-sm" role="status"></span>
                <i v-else class="bi bi-box-arrow-in-down"></i>
                {{ dispatching ? 'Memasukkan ke Antrean...' : 'Tambahkan Tugas ke Antrean' }}
            </button>
        </div>
    </div>

    <!-- Table Section: Job Queue Logs -->
    <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-200/80 mb-12">
        
        <!-- Table Header & Filters -->
        <div class="flex flex-col md:flex-row justify-between items-md-center gap-4 mb-6">
            <div>
                <h4 class="text-base font-bold text-slate-800 mb-0.5">Daftar Pekerjaan & Antrean Terkini</h4>
                <p class="text-xs text-slate-500 mb-0">Menampilkan seluruh status pekerjaan background yang tersimpan dalam sistem.</p>
            </div>

            <!-- Form Filters (Horizontal Grid) -->
            <div class="flex flex-wrap items-center gap-3">
                
                <!-- Status Filter -->
                <label for="filterStatus" class="visually-hidden">Filter Status</label>
                <select id="filterStatus" name="filter_status" v-model="filters.status" @change="fetchData(1)" class="h-9 px-3 rounded-lg border border-slate-200 bg-white text-xs font-semibold text-slate-600 focus:outline-none">
                    <option value="">-- Semua Status --</option>
                    <option value="pending">Pending</option>
                    <option value="processing">Processing</option>
                    <option value="completed">Completed</option>
                    <option value="failed">Failed</option>
                </select>

                <!-- Job Type Filter -->
                <label for="filterJobType" class="visually-hidden">Filter Jenis Pekerjaan</label>
                <select id="filterJobType" name="filter_job_type" v-model="filters.job_type" @change="fetchData(1)" class="h-9 px-3 rounded-lg border border-slate-200 bg-white text-xs font-semibold text-slate-600 focus:outline-none">
                    <option value="">-- Semua Jenis --</option>
                    <option value="DEMO_SYNC">DEMO_SYNC</option>
                    <option value="DEMO_EMAIL">DEMO_EMAIL</option>
                    <option value="CLEANUP_SESSIONS">CLEANUP_SESSIONS</option>
                </select>

                <!-- Tenant Filter (Super Admin Only) -->
                <?php if ($user_role === 'super_admin'): ?>
                <label for="filterTenantId" class="visually-hidden">Filter Sekolah</label>
                <select id="filterTenantId" name="filter_tenant_id" v-model="filters.tenant_id" @change="fetchData(1)" class="h-9 px-3 rounded-lg border border-slate-200 bg-white text-xs font-semibold text-slate-600 focus:outline-none">
                    <option value="">-- Semua Sekolah --</option>
                    <?php foreach ($tenantsList as $t): ?>
                        <option value="<?= htmlspecialchars($t['id']) ?>"><?= htmlspecialchars($t['nama_sekolah']) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php endif; ?>

                <button @click="fetchData(1)" class="w-9 h-9 border border-slate-200 hover:bg-slate-50 text-slate-500 rounded-lg flex items-center justify-center cursor-pointer transition">
                    <i class="bi bi-arrow-clockwise"></i>
                </button>
            </div>
        </div>

        <!-- Responsive Table -->
        <div class="table-responsive border border-slate-100 rounded-2xl overflow-hidden mb-6">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-slate-50/75 border-b border-slate-100">
                    <tr class="text-xs text-slate-500 font-bold">
                        <th scope="col" style="width: 80px;" class="py-3 px-4 font-bold text-slate-500">ID</th>
                        <th scope="col" style="width: 200px;" class="px-3 font-bold text-slate-500">Sekolah / Tenant</th>
                        <th scope="col" style="width: 140px;" class="px-3 font-bold text-slate-500">Jenis Pekerjaan</th>
                        <th scope="col" class="px-3 font-bold text-slate-500">Parameter Payload</th>
                        <th scope="col" style="width: 120px;" class="px-3 font-bold text-slate-500">Status</th>
                        <th scope="col" style="width: 180px;" class="px-3 font-bold text-slate-500">Waktu Pengerjaan</th>
                        <th scope="col" style="width: 100px;" class="text-center px-4 font-bold text-slate-500">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="loading">
                        <td colspan="7" class="text-center py-5 text-slate-400">
                            <div class="spinner-border spinner-border-sm text-blue-500 me-2" role="status"></div>
                            <span class="text-xs font-semibold">Memuat log pekerjaan...</span>
                        </td>
                    </tr>
                    <tr v-else-if="jobs.length === 0">
                        <td colspan="7" class="text-center py-5 text-slate-400">
                            <i class="bi bi-folder2-open d-block text-3xl mb-2 text-slate-300"></i>
                            <span class="text-xs">Tidak ada pekerjaan antrean yang ditemukan.</span>
                        </td>
                    </tr>
                    <tr v-else v-for="job in jobs" :key="job.id" class="text-xs border-b border-slate-100 hover:bg-slate-50/40">
                        <td class="py-3 px-4 font-mono font-bold text-slate-500">#{{ job.id }}</td>
                        <td class="px-3">
                            <div class="font-bold text-slate-700">{{ job.nama_sekolah || 'Sistem / Global' }}</div>
                            <div class="text-[10px] text-slate-400 font-mono" v-if="job.tenant_id">{{ job.tenant_id }}</div>
                        </td>
                        <td class="px-3">
                            <span class="bg-indigo-50 text-indigo-700 border border-indigo-100 px-2 py-0.5 rounded-md font-mono font-bold text-[10px]">
                                {{ job.job_type }}
                            </span>
                        </td>
                        <td class="px-3">
                            <div class="bg-slate-50 rounded-xl p-2.5 border border-slate-200/50 max-h-24 overflow-y-auto">
                                <ul class="list-disc ps-3.5 mb-0 text-slate-500 font-medium leading-relaxed">
                                    <li v-for="(val, key) in job.payload" :key="key">
                                        <strong class="text-slate-600 font-bold font-mono">{{ key }}:</strong> 
                                        <span class="font-mono text-slate-700">{{ val }}</span>
                                    </li>
                                </ul>
                                <div v-if="job.error_message" class="text-red-500 mt-2 pt-2 border-t border-red-100 font-semibold font-mono text-[10px]">
                                    <i class="bi bi-bug me-1"></i>Error: {{ job.error_message }}
                                </div>
                            </div>
                        </td>
                        <td class="px-3">
                            <span v-if="job.status === 'pending'" class="bg-amber-100 text-amber-800 border border-amber-200/60 px-2.5 py-1 rounded-full font-bold uppercase tracking-wider text-[9px] flex items-center justify-center gap-1.5 w-24">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span> Pending
                            </span>
                            <span v-else-if="job.status === 'processing'" class="bg-blue-100 text-blue-800 border border-blue-200/60 px-2.5 py-1 rounded-full font-bold uppercase tracking-wider text-[9px] flex items-center justify-center gap-1.5 w-24">
                                <span class="spinner-border spinner-border-sm text-blue-500" style="width:10px;height:10px;" role="status"></span> Proses
                            </span>
                            <span v-else-if="job.status === 'completed'" class="bg-emerald-100 text-emerald-800 border border-emerald-200/60 px-2.5 py-1 rounded-full font-bold uppercase tracking-wider text-[9px] flex items-center justify-center gap-1.5 w-24">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Selesai
                            </span>
                            <span v-else-if="job.status === 'failed'" class="bg-red-100 text-red-800 border border-red-200/60 px-2.5 py-1 rounded-full font-bold uppercase tracking-wider text-[9px] flex items-center justify-center gap-1.5 w-24">
                                <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span> Gagal
                            </span>
                        </td>
                        <td class="px-3 text-slate-500 font-medium font-mono leading-relaxed">
                            <div v-if="job.created_at"><i class="bi bi-plus-square text-slate-400 me-1"></i>{{ formatDateTime(job.created_at) }}</div>
                            <div v-if="job.reserved_at"><i class="bi bi-cpu text-blue-400 me-1"></i>{{ formatDateTime(job.reserved_at) }}</div>
                            <div v-if="job.completed_at"><i class="bi bi-patch-check text-emerald-400 me-1"></i>{{ formatDateTime(job.completed_at) }}</div>
                        </td>
                        <td class="px-4 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <!-- Retry Action -->
                                <button v-if="job.status === 'failed'" @click="retryJob(job.id)" title="Proses Ulang" 
                                        class="w-7 h-7 bg-amber-50 hover:bg-amber-100 text-amber-600 border border-amber-200/40 rounded-lg flex items-center justify-center cursor-pointer transition">
                                    <i class="bi bi-arrow-repeat text-sm"></i>
                                </button>
                                <!-- Delete Action -->
                                <button @click="deleteJob(job.id)" title="Hapus Pekerjaan" 
                                        class="w-7 h-7 bg-red-50 hover:bg-red-100 text-red-600 border border-red-200/40 rounded-lg flex items-center justify-center cursor-pointer transition">
                                    <i class="bi bi-trash text-sm"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination Section -->
        <div v-if="totalPages > 1" class="flex justify-between items-center bg-slate-50/70 p-3 rounded-2xl border border-slate-100">
            <span class="text-xs text-slate-500 font-medium">Halaman <strong class="text-slate-700 font-bold font-mono">{{ currentPage }}</strong> dari <strong class="text-slate-700 font-bold font-mono">{{ totalPages }}</strong></span>
            
            <div class="flex gap-2">
                <button :disabled="currentPage === 1" @click="fetchData(currentPage - 1)" 
                        class="px-3 py-1.5 bg-white border border-slate-200 hover:bg-slate-50 text-slate-600 rounded-lg font-bold text-xs cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed transition">
                    Sebelumnya
                </button>
                <button :disabled="currentPage === totalPages" @click="fetchData(currentPage + 1)" 
                        class="px-3 py-1.5 bg-white border border-slate-200 hover:bg-slate-50 text-slate-600 rounded-lg font-bold text-xs cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed transition">
                    Berikutnya
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Vue App Registration Script -->
<script>
{
    const { ref, reactive, onMounted, onUnmounted } = Vue;

    window.VueAppRegistry.register('#queueMonitoringApp', {
        setup() {
            const metrics = ref({ pending: 0, processing: 0, completed: 0, failed: 0, total: 0 });
            const jobs = ref([]);
            const currentPage = ref(1);
            const totalPages = ref(1);
            const loading = ref(false);

            // Filters
            const filters = reactive({
                status: '',
                job_type: '',
                tenant_id: ''
            });

            // Simulation Form State
            const simJobType = ref('DEMO_SYNC_SUCCESS');
            const simTenantId = ref('');
            const dispatching = ref(false);

            // Web Worker Trigger State
            const runningWorker = ref(false);
            
            // Auto refresh interval reference
            let refreshInterval = null;

            // Format date time helper
            const formatDateTime = (rawDateTime) => {
                if (!rawDateTime) return '';
                const d = new Date(rawDateTime.replace(/-/g, '/'));
                if (isNaN(d.getTime())) return rawDateTime;
                return d.toLocaleDateString('id-ID', {
                    day: '2-digit',
                    month: 'short',
                    year: 'numeric'
                }) + ' • ' + d.toLocaleTimeString('id-ID', {
                    hour: '2-digit',
                    minute: '2-digit'
                });
            };

            // Fetch queue database logs & counters
            const fetchData = async (page = 1) => {
                loading.value = true;
                currentPage.value = page;
                try {
                    const response = await axios.get('/SINTA-SaaS/api/v1/queue/data', {
                        params: {
                            page: page,
                            status: filters.status,
                            job_type: filters.job_type,
                            tenant_id: filters.tenant_id
                        }
                    });
                    
                    if (response.data && response.data.success) {
                        metrics.value = response.data.metrics;
                        jobs.value = response.data.jobs;
                        totalPages.value = response.data.total_pages;
                    }
                } catch (err) {
                    console.error('Failed to fetch queue data:', err);
                } finally {
                    loading.value = false;
                }
            };

            // Dispatch simulated job API request
            const dispatchSimJob = async () => {
                dispatching.value = true;
                
                let jobType = 'DEMO_SYNC';
                let payload = {};

                if (simJobType.value === 'DEMO_SYNC_SUCCESS') {
                    jobType = 'DEMO_SYNC';
                    payload = { force_fail: false, desc: 'Sinkronisasi profil data akademik dan sapras ke pusat.' };
                } else if (simJobType.value === 'DEMO_SYNC_FAIL') {
                    jobType = 'DEMO_SYNC';
                    payload = { force_fail: true, desc: 'Uji kegagalan sinkronisasi pendaftaran.' };
                } else if (simJobType.value === 'DEMO_EMAIL') {
                    jobType = 'DEMO_EMAIL';
                    payload = { subject: 'Bukti Pembayaran Pendaftaran SPMB Terverifikasi', recipient_count: 75 };
                }

                try {
                    const response = await axios.post('/SINTA-SaaS/api/v1/queue/dispatch', {
                        job_type: jobType,
                        payload: payload,
                        tenant_id: simTenantId.value
                    });

                    if (response.data && response.data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Tugas Ditambahkan',
                            text: response.data.message || 'Tugas simulasi masuk antrean.',
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 3000,
                            timerProgressBar: true
                        });
                        fetchData(1);
                    }
                } catch (err) {
                    console.error(err);
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal Memasukkan Antrean',
                        text: err.response?.data?.error || err.message || 'Terjadi kesalahan sistem.'
                    });
                } finally {
                    dispatching.value = false;
                }
            };

            // Run manual local worker from browser
            const runWorkerOnce = async () => {
                runningWorker.value = true;
                try {
                    const response = await axios.post('/SINTA-SaaS/api/v1/queue/run-worker');
                    
                    if (response.data) {
                        if (response.data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Worker Berhasil',
                                text: response.data.message || 'Pekerjaan berhasil diproses di background.',
                                confirmButtonColor: '#2563eb'
                            });
                        } else {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Worker Menjumpai Error',
                                text: response.data.error || 'Pekerjaan gagal diselesaikan.',
                                confirmButtonColor: '#2563eb'
                            });
                        }
                        // Refresh data
                        fetchData(currentPage.value);
                    }
                } catch (err) {
                    console.error(err);
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal Memicu Worker',
                        text: err.response?.data?.error || err.message || 'Terjadi kesalahan sistem saat memproses antrean.'
                    });
                } finally {
                    runningWorker.value = false;
                }
            };

            // Retry failed job
            const retryJob = async (jobId) => {
                try {
                    const response = await axios.post('/SINTA-SaaS/api/v1/queue/retry', { id: jobId });
                    if (response.data && response.data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Pekerjaan Diatur Ulang',
                            text: response.data.message || 'Pekerjaan siap dijalankan kembali.',
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 3000,
                            timerProgressBar: true
                        });
                        fetchData(currentPage.value);
                    }
                } catch (err) {
                    console.error(err);
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal Memproses Ulang',
                        text: err.response?.data?.error || err.message || 'Terjadi kesalahan sistem.'
                    });
                }
            };

            // Delete job from queue
            const deleteJob = (jobId) => {
                Swal.fire({
                    title: 'Hapus Pekerjaan?',
                    text: `Apakah Anda yakin ingin menghapus pekerjaan #${jobId} dari catatan antrean?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal'
                }).then(async (result) => {
                    if (result.isConfirmed) {
                        try {
                            const response = await axios.post('/SINTA-SaaS/api/v1/queue/delete', { id: jobId });
                            if (response.data && response.data.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Terhapus',
                                    text: response.data.message || 'Pekerjaan berhasil dihapus.',
                                    toast: true,
                                    position: 'top-end',
                                    showConfirmButton: false,
                                    timer: 2000,
                                    timerProgressBar: true
                                });
                                fetchData(currentPage.value);
                            }
                        } catch (err) {
                            console.error(err);
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal Menghapus',
                                text: err.response?.data?.error || err.message || 'Terjadi kesalahan sistem.'
                            });
                        }
                    }
                });
            };

            onMounted(() => {
                fetchData(1);
                
                // Set interval untuk auto-refresh data antrean setiap 10 detik agar interaktif
                refreshInterval = setInterval(() => {
                    fetchData(currentPage.value);
                }, 10000);
            });

            onUnmounted(() => {
                if (refreshInterval) {
                    clearInterval(refreshInterval);
                }
            });

            return {
                metrics,
                jobs,
                currentPage,
                totalPages,
                loading,
                filters,
                simJobType,
                simTenantId,
                dispatching,
                runningWorker,
                formatDateTime,
                fetchData,
                dispatchSimJob,
                runWorkerOnce,
                retryJob,
                deleteJob
            };
        }
    });
}
</script>
