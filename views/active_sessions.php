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
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="fw-bold text-dark mb-0">
                    <i class="bi bi-graph-up text-primary me-2"></i> Tren Login Harian (30 Hari Terakhir)
                </h5>
                <span class="fs-9 text-muted font-monospace">Metrik: Pengguna Unik</span>
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
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
                <h5 class="fw-bold text-dark mb-0">
                    <i class="bi bi-people-fill text-success me-2"></i> Pengguna Online Saat Ini
                </h5>
                <div class="text-md-end text-muted fs-8">
                    Menampilkan <strong class="text-success">{{ onlineUsers.length }}</strong> pengguna aktif dalam 15 menit terakhir
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
                        <tr v-else v-for="user in onlineUsers" :key="user.id" class="fs-8">
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
            const chartRawData = ref([]);
            const loading = ref(false);
            const cleaning = ref(false);

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
                    const response = await axios.get('/dapodik-spmb/api/v1/sessions/data');
                    if (response.data && response.data.success) {
                        onlineUsers.value = response.data.online_users;
                        chartRawData.value = response.data.chart_data;
                        
                        // Render or update chart
                        renderChart();
                    }
                } catch (err) {
                    console.error('Failed to load session monitoring data:', err);
                } finally {
                    loading.value = false;
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

                // If empty data, show placeholder
                if (chartRawData.value.length === 0) {
                    return;
                }

                // Map data labels and values
                const labels = chartRawData.value.map(d => {
                    const parts = d.tanggal_login.split('-');
                    return `${parts[2]}/${parts[1]}`; // format DD/MM
                });
                const values = chartRawData.value.map(d => d.total_logins);

                myChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Pengguna Unik Login',
                            data: values,
                            borderColor: '#2563eb',
                            backgroundColor: 'rgba(37, 99, 235, 0.05)',
                            borderWidth: 2.5,
                            fill: true,
                            tension: 0.35,
                            pointBackgroundColor: '#2563eb',
                            pointBorderColor: '#ffffff',
                            pointHoverRadius: 6,
                            pointHoverBackgroundColor: '#1d4ed8'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
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
                            const response = await axios.post('/dapodik-spmb/api/v1/sessions/retention', {
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
                                text: err.response?.data?.error || err.message || 'Terjadi kesalahan sistem.'
                            });
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
                loading,
                cleaning,
                retentionDate,
                maxRetentionDate,
                parseUserAgent,
                formatDateTime,
                executeRetention
            };
        }
    });
}
</script>
