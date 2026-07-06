<?php
/**
 * View: Monitoring Sesi Aktif & Analitik
 * Bagian ini dimuat secara dinamis oleh views/layout/master.php di area #main-content.
 */
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-2 mb-4 border-bottom">
    <div>
        <h2 class="fw-bold text-dark mb-1">Monitoring Sesi & Analitik</h2>
        <p class="text-muted fs-7">Memantau pengguna yang sedang online secara real-time dan menganalisis tren login harian.</p>
    </div>
</div>

<div id="activeSessionsApp" v-cloak class="row g-4 mb-5">
    
    <!-- Left: Line Chart Analitik -->
    <div class="col-lg-8">
        <div class="card border-0 rounded-4 shadow-sm p-4 h-100" style="background-color: #ffffff;">
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                <h5 class="fw-bold text-dark mb-0">
                    <i class="bi bi-graph-up text-primary me-2"></i> Tren Sesi & Aktivitas
                </h5>
                <select class="form-select form-select-sm w-auto rounded-3" v-model="chartTimeframe" @change="fetchData">
                    <option value="30_minutes">30 Menit Terakhir</option>
                    <option value="1_hour">1 Jam Terakhir</option>
                    <option value="1_day">1 Hari Terakhir</option>
                    <option value="15_days">15 Hari Terakhir</option>
                    <option value="30_days">30 Hari Terakhir</option>
                </select>
            </div>
            
            <div class="chart-container position-relative" style="height: 320px; width: 100%;">
                <canvas id="loginTrendsChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Right: Data Retention & Clean Log Sesi -->
    <div class="col-lg-4">
        <div class="card border-0 rounded-4 shadow-sm p-4 h-100 d-flex flex-column justify-content-between" style="background-color: #ffffff;">
            <div>
                <h5 class="fw-bold text-dark mb-3 pb-2 border-bottom">
                    <i class="bi bi-trash3 text-danger me-2"></i> Retensi & Hapus Log Sesi
                </h5>
                <p class="text-muted fs-8 mb-4">
                    Hapus log riwayat sesi lama sebelum tanggal tertentu untuk menghemat ruang penyimpanan database. Sesi aktif yang sedang berjalan hari ini tidak akan terpengaruh.
                </p>
                
                <div class="mb-4">
                    <label for="dateLimit" class="form-label fs-8 fw-semibold text-secondary">Hapus Log Sebelum / Pada Tanggal:</label>
                    <input type="date" id="dateLimit" 
                           class="form-control rounded-3 fs-8" 
                           v-model="retentionDate" 
                           :max="maxRetentionDate">
                </div>
            </div>

            <div>
                <button class="btn btn-danger rounded-3 w-100 py-2.5 fs-8 fw-semibold shadow-xs" 
                        @click="executeRetention" 
                        :disabled="cleaning || !retentionDate">
                    <span v-if="cleaning" class="spinner-border spinner-border-sm me-2" role="status"></span>
                    <i v-else class="bi bi-shield-x me-1"></i> Bersihkan Riwayat Sesi
                </button>
            </div>
        </div>
    </div>

    <!-- Bottom: Tabel User Sedang Online (Aktivitas 15 Menit Terakhir) -->
    <div class="col-12">
        <div class="card border-0 rounded-4 shadow-sm p-4" style="background-color: #ffffff;">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
                <div>
                    <h5 class="fw-bold text-dark mb-1">
                        <i class="bi bi-clock-history text-success me-2"></i> Riwayat Sesi Pengguna
                    </h5>
                    <div class="text-muted fs-8">
                        Menampilkan <strong class="text-success">{{ filteredOnlineUsers.length }}</strong> sesi rekaman
                    </div>
                </div>
                
                <div class="d-flex flex-column flex-sm-row gap-2">
                    <input type="date" class="form-control form-control-sm rounded-3" v-model="startDate" title="Tanggal Mulai">
                    <input type="date" class="form-control form-control-sm rounded-3" v-model="endDate" title="Tanggal Akhir">
                    <button class="btn btn-sm btn-primary rounded-3 px-3" @click="fetchData">
                        <i class="bi bi-funnel"></i> Filter
                    </button>
                    <div class="input-group input-group-sm ms-sm-2" style="max-width: 220px;">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control border-start-0 ps-0 bg-light" placeholder="Cari nama, role, IP..." v-model="searchQuery">
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="table-responsive rounded-3 border overflow-hidden">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr class="fs-8 text-secondary">
                            <th scope="col" style="width: 220px;">Nama Pengguna</th>
                            <th scope="col" style="width: 140px;">Peran</th>
                            <th scope="col" style="width: 140px;">IP Address</th>
                            <th scope="col">Browser / User Agent</th>
                            <th scope="col" style="width: 200px;">Aktivitas Terakhir</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Loader row -->
                        <tr v-if="loading">
                            <td colspan="5" class="text-center py-5 text-muted">
                                <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                                <span class="fs-8">Memuat daftar pengguna online...</span>
                            </td>
                        </tr>
                        <!-- Empty row -->
                        <tr v-else-if="onlineUsers.length === 0">
                            <td colspan="5" class="text-center py-5 text-muted">
                                <i class="bi bi-person-dash d-block fs-3 mb-2 text-secondary"></i>
                                <span class="fs-8">Tidak ada pengguna yang terdeteksi aktif saat ini.</span>
                            </td>
                        </tr>
                        <!-- Data rows -->
                        <tr v-else v-for="user in paginatedOnlineUsers" :key="user.id" class="fs-8">
                            <td>
                                <div class="fw-semibold text-dark">{{ user.nama_lengkap }}</div>
                                <div class="text-muted" style="font-size: 0.7rem;" v-if="user.nama_sekolah">
                                    Sekolah: {{ user.nama_sekolah }}
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border text-uppercase px-2 py-1 fs-9 font-monospace" style="letter-spacing: 0.5px;">
                                    {{ user.user_role }}
                                </span>
                            </td>
                            <td class="font-monospace text-muted">{{ user.ip_address }}</td>
                            <td class="text-truncate text-muted fs-8.5" style="max-width: 350px;" :title="user.user_agent">
                                {{ parseUserAgent(user.user_agent) }}
                            </td>
                            <td class="text-muted font-monospace">{{ formatDateTime(user.last_activity) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div class="d-flex justify-content-between align-items-center mt-3">
                <div class="d-flex align-items-center gap-2">
                    <span class="fs-8 text-muted">Tampilkan</span>
                    <select class="form-select form-select-sm w-auto rounded-3" v-model="onlinePerPage" @change="onlinePage = 1">
                        <option :value="10">10</option>
                        <option :value="25">25</option>
                        <option :value="50">50</option>
                        <option :value="100">100</option>
                    </select>
                    <span class="fs-8 text-muted">baris</span>
                </div>
                <div class="btn-group">
                    <button class="btn btn-sm btn-outline-secondary" @click="onlinePage--" :disabled="onlinePage <= 1">Sebelumnya</button>
                    <button class="btn btn-sm btn-outline-secondary" @click="onlinePage++" :disabled="onlinePage >= onlineTotalPages">Selanjutnya</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom: Tabel Log Jejak Keamanan -->
    <div class="col-12 mt-4">
        <div class="card border-0 rounded-4 shadow-sm p-4" style="background-color: #ffffff;">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
                <div>
                    <h5 class="fw-bold text-dark mb-1">
                        <i class="bi bi-shield-lock-fill text-danger me-2"></i> Log Jejak Keamanan (Audit Trail)
                    </h5>
                    <div class="text-muted fs-8">
                        Menampilkan <strong class="text-danger">{{ auditLogs.length }}</strong> rekaman aktivitas Login & Logout terbaru
                    </div>
                </div>
                <div class="d-flex flex-column flex-sm-row gap-2">
                    <input type="date" class="form-control form-control-sm rounded-3" v-model="auditStartDate" title="Tanggal Mulai">
                    <input type="date" class="form-control form-control-sm rounded-3" v-model="auditEndDate" title="Tanggal Akhir">
                    <button class="btn btn-sm btn-primary rounded-3 px-3" @click="fetchAuditLogs">
                        <i class="bi bi-funnel"></i> Filter
                    </button>
                    <button class="btn btn-sm btn-outline-secondary rounded-3 px-3" @click="fetchAuditLogs" title="Segarkan">
                        <i class="bi bi-arrow-clockwise"></i>
                    </button>
                    <button class="btn btn-sm btn-danger rounded-3 px-3" @click="executeAuditRetention" title="Hapus Log">
                        <i class="bi bi-trash3"></i> Hapus
                    </button>
                </div>
            </div>

            <!-- Table -->
            <div class="table-responsive rounded-3 border overflow-hidden">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr class="fs-8 text-secondary">
                            <th scope="col" style="width: 220px;">Waktu Kejadian</th>
                            <th scope="col" style="width: 140px;">Aktivitas</th>
                            <th scope="col">Nama Pengguna</th>
                            <th scope="col" style="width: 140px;">Peran</th>
                            <th scope="col" style="width: 150px;">IP Address</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Loader row -->
                        <tr v-if="loadingAudit">
                            <td colspan="5" class="text-center py-5 text-muted">
                                <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                                <span class="fs-8">Memuat log audit...</span>
                            </td>
                        </tr>
                        <!-- Empty row -->
                        <tr v-else-if="auditLogs.length === 0">
                            <td colspan="5" class="text-center py-5 text-muted">
                                <i class="bi bi-shield-check d-block fs-3 mb-2 text-secondary"></i>
                                <span class="fs-8">Belum ada aktivitas yang terekam.</span>
                            </td>
                        </tr>
                        <!-- Data rows -->
                        <tr v-else v-for="log in paginatedAuditLogs" :key="log.id" class="fs-8">
                            <td class="text-muted font-monospace">{{ formatDateTime(log.created_at) }}</td>
                            <td>
                                <span v-if="log.action === 'LOGIN'" class="badge bg-success text-white border text-uppercase px-2 py-1 fs-9 font-monospace" style="letter-spacing: 0.5px;">
                                    <i class="bi bi-box-arrow-in-right me-1"></i> LOGIN
                                </span>
                                <span v-else-if="log.action === 'LOGOUT'" class="badge bg-secondary text-white border text-uppercase px-2 py-1 fs-9 font-monospace" style="letter-spacing: 0.5px;">
                                    <i class="bi bi-box-arrow-left me-1"></i> LOGOUT
                                </span>
                                <span v-else class="badge bg-dark text-white border text-uppercase px-2 py-1 fs-9 font-monospace" style="letter-spacing: 0.5px;">
                                    {{ log.action }}
                                </span>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark">{{ log.nama_lengkap }}</div>
                                <div class="text-muted" style="font-size: 0.7rem;" v-if="log.nama_sekolah">
                                    Sekolah: {{ log.nama_sekolah }}
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border text-uppercase px-2 py-1 fs-9 font-monospace" style="letter-spacing: 0.5px;">
                                    {{ log.user_role }}
                                </span>
                            </td>
                            <td class="font-monospace text-muted">{{ log.ip_address }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-3">
                <div class="d-flex align-items-center gap-2">
                    <span class="fs-8 text-muted">Tampilkan</span>
                    <select class="form-select form-select-sm w-auto rounded-3" v-model="auditPerPage" @change="auditPage = 1">
                        <option :value="10">10</option>
                        <option :value="25">25</option>
                        <option :value="50">50</option>
                        <option :value="100">100</option>
                    </select>
                    <span class="fs-8 text-muted">baris</span>
                </div>
                <div class="btn-group">
                    <button class="btn btn-sm btn-outline-secondary" @click="auditPage--" :disabled="auditPage <= 1">Sebelumnya</button>
                    <button class="btn btn-sm btn-outline-secondary" @click="auditPage++" :disabled="auditPage >= auditTotalPages">Selanjutnya</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Vue App Registration Script -->
<script>
{
    const { ref, onMounted, onUnmounted } = Vue;

    window.VueAppRegistry.register('#activeSessionsApp', {
        setup() {
            const onlineUsers = ref([]);
            const auditLogs = ref([]);
            const chartRawData = ref([]);
            const auditChartRawData = ref([]);
            const loading = ref(false);
            const loadingAudit = ref(false);
            const cleaning = ref(false);

            // Timeframes & Filters
            const chartTimeframe = ref('30_days');
            const startDate = ref('');
            const endDate = ref('');
            const searchQuery = ref('');
            
            const onlinePerPage = ref(10);
            const onlinePage = ref(1);

            const auditStartDate = ref('');
            const auditEndDate = ref('');
            const auditPerPage = ref(10);
            const auditPage = ref(1);

            // Computed property for filtering
            const filteredOnlineUsers = Vue.computed(() => {
                let list = onlineUsers.value;
                if (searchQuery.value) {
                    const q = searchQuery.value.toLowerCase();
                    list = list.filter(u => 
                        (u.nama_lengkap && u.nama_lengkap.toLowerCase().includes(q)) ||
                        (u.user_role && u.user_role.toLowerCase().includes(q)) ||
                        (u.ip_address && u.ip_address.includes(q))
                    );
                }
                return list;
            });

            const paginatedOnlineUsers = Vue.computed(() => {
                const start = (onlinePage.value - 1) * onlinePerPage.value;
                return filteredOnlineUsers.value.slice(start, start + onlinePerPage.value);
            });
            const onlineTotalPages = Vue.computed(() => Math.ceil(filteredOnlineUsers.value.length / onlinePerPage.value) || 1);

            const paginatedAuditLogs = Vue.computed(() => {
                const start = (auditPage.value - 1) * auditPerPage.value;
                return auditLogs.value.slice(start, start + auditPerPage.value);
            });
            const auditTotalPages = Vue.computed(() => Math.ceil(auditLogs.value.length / auditPerPage.value) || 1);

            // Retention
            const retentionDate = ref('');
            const maxRetentionDate = ref('');

            // Chart instance reference
            let myChart = null;

            // Simple user agent parser to make it human readable
            const parseUserAgent = (ua) => {
                if (!ua) return 'Unknown';
                if (ua.includes('Firefox/')) return 'Mozilla Firefox';
                if (ua.includes('Chrome/')) {
                    if (ua.includes('Edg/')) return 'Microsoft Edge (Chromium)';
                    if (ua.includes('OPR/')) return 'Opera Browser';
                    return 'Google Chrome';
                }
                if (ua.includes('Safari/') && !ua.includes('Chrome/')) return 'Apple Safari';
                if (ua.includes('MSIE') || ua.includes('Trident/')) return 'Internet Explorer';
                return ua.length > 50 ? ua.substring(0, 47) + '...' : ua;
            };

            // Format date time
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
                    minute: '2-digit',
                    second: '2-digit'
                });
            };

            // Fetch session data
            const fetchData = async () => {
                loading.value = true;
                try {
                    const response = await axios.get('/SINTA-SaaS/api/v1/sessions/data', {
                        params: {
                            timeframe: chartTimeframe.value,
                            start_date: startDate.value,
                            end_date: endDate.value
                        }
                    });
                    if (response.data && response.data.success) {
                        onlineUsers.value = response.data.online_users;
                        chartRawData.value = response.data.chart_data;
                        auditChartRawData.value = response.data.audit_chart_data || [];
                        onlinePage.value = 1;
                        
                        // Render or update chart
                        renderChart();
                    }
                } catch (err) {
                    console.error('Failed to load session monitoring data:', err);
                } finally {
                    loading.value = false;
                }
            };

            // Fetch audit logs
            const fetchAuditLogs = async () => {
                loadingAudit.value = true;
                try {
                    const response = await axios.get('/SINTA-SaaS/api/v1/sessions/audit', {
                        params: {
                            start_date: auditStartDate.value,
                            end_date: auditEndDate.value
                        }
                    });
                    if (response.data && response.data.success) {
                        auditLogs.value = response.data.audit_logs;
                        auditPage.value = 1;
                    }
                } catch (err) {
                    console.error('Failed to load audit logs:', err);
                } finally {
                    loadingAudit.value = false;
                }
            };

            // Render daily login trend chart
            const renderChart = () => {
                const ctx = document.getElementById('loginTrendsChart');
                if (!ctx) return;

                // Destroy old instance if exists to prevent memory leaks and chart overlapping
                if (myChart) {
                    myChart.destroy();
                }

                // If empty data, provide dummy labels to draw empty chart grid
                if (chartRawData.value.length === 0 && auditChartRawData.value.length === 0) {
                    chartRawData.value = [];
                    auditChartRawData.value = [];
                }

                // Get all labels from both arrays and sort them
                const labelSet = new Set();
                chartRawData.value.forEach(d => labelSet.add(d.label || d.tanggal_login));
                auditChartRawData.value.forEach(d => labelSet.add(d.label));
                let allLabels = Array.from(labelSet).sort();

                // formatting labels for display
                const displayLabels = allLabels.map(label => {
                    if (chartTimeframe.value === '30_days' || chartTimeframe.value === '15_days') {
                        const parts = (label || '').split('-');
                        if (parts.length >= 3) {
                            return `${parts[2]}/${parts[1]}`; // format DD/MM
                        }
                    }
                    return label || ''; 
                });

                // Map data labels and values
                const uniqueUsersValues = allLabels.map(l => {
                    const row = chartRawData.value.find(d => (d.label || d.tanggal_login) === l);
                    return row ? row.total_users : 0;
                });
                const loginValues = allLabels.map(l => {
                    const row = auditChartRawData.value.find(d => d.label === l);
                    return row ? row.total_logins : 0;
                });
                const logoutValues = allLabels.map(l => {
                    const row = auditChartRawData.value.find(d => d.label === l);
                    return row ? row.total_logouts : 0;
                });

                myChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: displayLabels.length > 0 ? displayLabels : ['(Belum ada aktivitas)'],
                        datasets: [
                            {
                                label: 'Pengguna Unik',
                                data: uniqueUsersValues.length > 0 ? uniqueUsersValues : [0],
                                borderColor: '#2563eb', // Bootstrap Primary Blue
                                backgroundColor: 'rgba(37, 99, 235, 0.05)',
                                borderWidth: 2.5,
                                fill: true,
                                tension: 0.35,
                                pointBackgroundColor: '#2563eb',
                                pointBorderColor: '#ffffff',
                                pointHoverRadius: 6,
                                pointHoverBackgroundColor: '#1d4ed8'
                            },
                            {
                                label: 'Aktivitas Login',
                                data: loginValues.length > 0 ? loginValues : [0],
                                borderColor: '#198754', // Bootstrap Success
                                backgroundColor: 'rgba(25, 135, 84, 0.05)',
                                borderWidth: 2.5,
                                fill: true,
                                tension: 0.35,
                                pointBackgroundColor: '#198754',
                                pointBorderColor: '#ffffff',
                                pointHoverRadius: 6,
                                pointHoverBackgroundColor: '#146c43'
                            },
                            {
                                label: 'Aktivitas Logout',
                                data: logoutValues.length > 0 ? logoutValues : [0],
                                borderColor: '#6c757d', // Bootstrap Secondary
                                backgroundColor: 'rgba(108, 117, 125, 0.05)',
                                borderWidth: 2.5,
                                fill: true,
                                tension: 0.35,
                                pointBackgroundColor: '#6c757d',
                                pointBorderColor: '#ffffff',
                                pointHoverRadius: 6,
                                pointHoverBackgroundColor: '#5c636a'
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: true,
                                position: 'top',
                                labels: {
                                    usePointStyle: true,
                                    boxWidth: 8
                                }
                            },
                            tooltip: {
                                mode: 'index',
                                intersect: false,
                                padding: 10,
                                titleFont: { size: 12, weight: 'bold' },
                                bodyFont: { size: 12 },
                                backgroundColor: '#0f172a'
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    stepSize: 1,
                                    color: '#64748b',
                                    font: { size: 10 }
                                },
                                grid: {
                                    color: '#f1f5f9'
                                }
                            },
                            x: {
                                ticks: {
                                    color: '#64748b',
                                    font: { size: 10 }
                                },
                                grid: {
                                    display: false
                                }
                            }
                        }
                    }
                });
            };

            // Retention cleanup execution
            const executeRetention = () => {
                if (!retentionDate.value) return;

                Swal.fire({
                    title: 'Apakah Anda Yakin?',
                    text: `Anda akan menghapus log riwayat sesi sebelum/pada tanggal ${retentionDate.value}. Tindakan ini tidak dapat dibatalkan!`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Ya, Bersihkan Log!',
                    cancelButtonText: 'Batal'
                }).then(async (result) => {
                    if (result.isConfirmed) {
                        cleaning.value = true;
                        try {
                            const response = await axios.post('/SINTA-SaaS/api/v1/sessions/retention', {
                                date_limit: retentionDate.value
                            });
                            
                            if (response.data && response.data.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Log Dibersihkan',
                                    text: response.data.message || 'Data log retensi berhasil dihapus.',
                                    confirmButtonColor: '#2563eb'
                                });
                                // Reload data
                                fetchData();
                            }
                        } catch (err) {
                            console.error(err);
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal Membersihkan',
                                text: (err && err.response && err.response.data && err.response.data.error) || err.message || 'Terjadi kesalahan sistem.'
                            });
                        } finally {
                            cleaning.value = false;
                        }
                    }
                });
            };

            const executeAuditRetention = () => {
                const dateLimit = auditEndDate.value || new Date().toISOString().split('T')[0];
                Swal.fire({
                    title: 'Hapus Log Audit?',
                    text: `Anda akan menghapus log jejak keamanan (Login/Logout) sebelum/pada tanggal ${dateLimit}. Tindakan ini permanen!`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal'
                }).then(async (result) => {
                    if (result.isConfirmed) {
                        cleaning.value = true;
                        try {
                            const response = await axios.post('/SINTA-SaaS/api/v1/sessions/audit/retention', {
                                date_limit: dateLimit
                            });
                            
                            if (response.data && response.data.success) {
                                Swal.fire('Log Dihapus', response.data.message, 'success');
                                fetchAuditLogs();
                            }
                        } catch (err) {
                            console.error(err);
                            Swal.fire('Gagal Membersihkan', (err && err.response && err.response.data && err.response.data.error) || err.message, 'error');
                        } finally {
                            cleaning.value = false;
                        }
                    }
                });
            };

            onMounted(() => {
                // Set max retention date to yesterday (cannot clear today's active sessions)
                const yesterday = new Date();
                yesterday.setDate(yesterday.getDate() - 1);
                maxRetentionDate.value = yesterday.toISOString().split('T')[0];
                
                // Fetch data
                fetchData();
                fetchAuditLogs();
            });

            onUnmounted(() => {
                // Clean up chart instance on page change to avoid memory leak (essential for Turbo Drive)
                if (myChart) {
                    myChart.destroy();
                    myChart = null;
                }
            });

            return {
                onlineUsers,
                filteredOnlineUsers,
                auditLogs,
                loading,
                loadingAudit,
                cleaning,
                retentionDate,
                maxRetentionDate,
                chartTimeframe,
                startDate,
                endDate,
                searchQuery,
                onlinePerPage,
                onlinePage,
                onlineTotalPages,
                paginatedOnlineUsers,
                auditStartDate,
                auditEndDate,
                auditPerPage,
                auditPage,
                auditTotalPages,
                paginatedAuditLogs,
                fetchData,
                fetchAuditLogs,
                executeAuditRetention,
                parseUserAgent,
                formatDateTime,
                executeRetention
            };
        }
    });
}
</script>
