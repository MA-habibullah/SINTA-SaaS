<?php
/**
 * View: Log Aktivitas Sistem (Audit Trail)
 * Bagian ini dimuat secara dinamis oleh views/layout/master.php di area #main-content.
 */
$userRole = $_SESSION['role_name'] ?? '';
?>

<style>
    /* Styled, visible horizontal scrollbar for tables on the activity logs page */
    #activityLogsApp .table-responsive {
        overflow-x: auto !important;
    }
    #activityLogsApp .table-responsive::-webkit-scrollbar {
        height: 8px;
    }
    #activityLogsApp .table-responsive::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 4px;
    }
    #activityLogsApp .table-responsive::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 4px;
        border: 2px solid #f1f5f9;
    }
    #activityLogsApp .table-responsive::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }

    /* High contrast color overrides for WCAG compliance (ratio > 4.5:1 on light backgrounds) */
    .text-slate-600 {
        color: #475569 !important;
    }
    .text-slate-700 {
        color: #334155 !important;
    }
    #activityLogsApp .text-muted,
    #activityLogsApp .text-secondary,
    .swal2-container .text-muted,
    .swal2-container .text-secondary {
        color: #475569 !important;
    }
    #activityLogsApp .bg-light .text-primary {
        color: #1d4ed8 !important;
    }
</style>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-2 mb-4 border-bottom">
    <div>
        <h2 class="fw-bold text-dark mb-1">Audit Trail & Log Aktivitas</h2>
        <p class="text-slate-600 fs-7">Memantau rekaman aktivitas penambahan, pembaruan, dan penghapusan data sistem secara real-time.</p>
    </div>
</div>

<!-- Main Activity Logs Card (Vue Mounted) -->
<div id="activityLogsApp" v-cloak class="card border-0 rounded-4 shadow-sm p-4 mb-5" style="background-color: #ffffff;">
    <!-- Top Filter Area -->
    <div class="d-flex flex-column gap-3 mb-4">
        <div class="row g-2 align-items-center">
            <!-- Search bar -->
            <div class="col-md-4">
                <div class="position-relative">
                    <label for="logSearchQuery" class="visually-hidden">Cari Log Aktivitas</label>
                    <i class="bi bi-search position-absolute text-slate-600" style="left: 12px; top: 50%; transform: translateY(-50%); font-size: 0.85rem;"></i>
                    <input type="text" id="logSearchQuery" name="search_query" class="form-control rounded-pill ps-5 fs-8" 
                           v-model="searchQuery" 
                           @input="onSearchInput" 
                           placeholder="Cari aksi, tabel, user, IP...">
                </div>
            </div>
            
            <!-- School/Tenant Filter (Super Admin Only) -->
            <div v-if="isSuperAdmin" class="col-md-3">
                <label for="filterTenant" class="visually-hidden">Filter Sekolah</label>
                <select id="filterTenant" name="tenant_filter" class="form-select rounded-pill fs-8" v-model="selectedTenant" @change="onFilterChange">
                    <option value="">Semua Sekolah</option>
                    <option value="system">Sistem (Super Admin)</option>
                    <option v-for="t in tenantOptions" :key="t.id" :value="t.id">
                        {{ t.nama_sekolah }} ({{ t.npsn }})
                    </option>
                </select>
            </div>

            <!-- Role Filter -->
            <div class="col-md-3">
                <label for="filterRole" class="visually-hidden">Filter Role</label>
                <select id="filterRole" name="role_filter" class="form-select rounded-pill fs-8 text-capitalize" v-model="selectedRole" @change="onFilterChange">
                    <option value="">Semua Role</option>
                    <option v-for="r in roleOptions" :key="r" :value="r">
                        {{ r }}
                    </option>
                </select>
            </div>

            <!-- Total count badge -->
            <div class="col-md text-md-end text-slate-600 fs-8">
                Menampilkan <strong class="text-dark">{{ logs.length }}</strong> dari <strong class="text-dark">{{ totalLogs }}</strong> entri
            </div>
        </div>
    </div>

    <!-- Table Container -->
    <div class="table-responsive rounded-3 border">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr class="fs-8 text-secondary">
                    <th scope="col" style="width: 140px;">Waktu</th>
                    <th scope="col" v-if="isSuperAdmin" style="width: 180px;">Sekolah</th>
                    <th scope="col" style="width: 200px;">Aktor / Peran</th>
                    <th scope="col" style="width: 180px;">Aksi / Tabel</th>
                    <th scope="col">Perubahan Nilai Data</th>
                    <th scope="col" class="text-center" style="width: 100px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <!-- Loader row -->
                <tr v-if="loading">
                    <td :colspan="isSuperAdmin ? 6 : 5" class="text-center py-5 text-muted">
                        <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                        <span class="fs-8">Memuat data log aktivitas...</span>
                    </td>
                </tr>
                <!-- Empty row -->
                <tr v-else-if="logs.length === 0">
                    <td :colspan="isSuperAdmin ? 6 : 5" class="text-center py-5 text-muted">
                        <i class="bi bi-journal-x d-block fs-3 mb-2 text-secondary"></i>
                        <span class="fs-8">Tidak ada data log aktivitas yang cocok.</span>
                    </td>
                </tr>
                <!-- Data rows -->
                <tr v-else v-for="log in logs" :key="log.id" class="fs-8">
                    <!-- Waktu -->
                    <td class="text-muted font-monospace">{{ formatDateTime(log.created_at) }}</td>
                    
                    <!-- Sekolah -->
                    <td v-if="isSuperAdmin">
                        <div class="fw-semibold text-dark">{{ log.nama_sekolah || 'Sistem (Global)' }}</div>
                    </td>
                    
                    <!-- Aktor & Peran -->
                    <td>
                        <div class="fw-semibold text-dark">{{ log.actor_name || 'System' }}</div>
                        <div class="text-muted font-monospace" style="font-size: 0.725rem; margin-top: 1px;">
                            <span class="badge bg-light text-secondary border text-uppercase px-1.5 py-0.5 fs-10 me-1">
                                {{ log.user_role }}
                            </span>
                            <span>IP: {{ log.ip_address }}</span>
                        </div>
                    </td>
                    
                    <!-- Aksi & Tabel -->
                    <td>
                        <div class="d-flex align-items-center gap-1.5 mb-1">
                            <span :class="{
                                'badge bg-success bg-opacity-10 text-success border border-success border-opacity-20': log.action === 'INSERT',
                                'badge bg-warning bg-opacity-10 text-warning-emphasis border border-warning border-opacity-20': log.action === 'UPDATE',
                                'badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-20': log.action === 'DELETE'
                            }" class="px-2 py-0.5 rounded fs-9 fw-semibold tracking-wider font-monospace">
                                {{ log.action }}
                            </span>
                            <code class="text-primary font-monospace fs-8">{{ log.table_name }}</code>
                        </div>
                        <div class="text-muted font-monospace" style="font-size: 0.7rem;">ID: {{ log.record_id }}</div>
                    </td>
                    
                    <!-- Perubahan Data (Pills layout) -->
                    <td>
                        <div v-html="getChanges(log)"></div>
                    </td>
                    
                    <!-- Aksi Tombol -->
                    <td class="text-center">
                        <button class="btn btn-sm btn-outline-primary rounded-3 px-3 py-1 fs-8 shadow-xs" @click="showDetail(log)">
                            <i class="bi bi-eye-fill me-1"></i> Detail
                        </button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Pagination Controls -->
    <div v-if="totalPages > 1" class="d-flex justify-content-between align-items-center mt-4">
        <div>
            <button class="btn btn-sm btn-outline-secondary rounded-3 px-3 py-1.5 fs-8 shadow-xs" 
                    :disabled="currentPage === 1 || loading" 
                    @click="changePage(currentPage - 1)">
                <i class="bi bi-chevron-left me-1"></i> Sebelumnya
            </button>
        </div>
        <div class="d-flex gap-1 align-items-center">
            <span class="fs-8 text-muted">Halaman {{ currentPage }} dari {{ totalPages }}</span>
        </div>
        <div>
            <button class="btn btn-sm btn-outline-secondary rounded-3 px-3 py-1.5 fs-8 shadow-xs" 
                    :disabled="currentPage === totalPages || loading" 
                    @click="changePage(currentPage + 1)">
                Berikutnya <i class="bi bi-chevron-right ms-1"></i>
            </button>
        </div>
    </div>
</div>

<!-- Vue App Registration Script -->
<script>
{
    const { ref, onMounted } = Vue;

    window.VueAppRegistry.register('#activityLogsApp', {
        setup() {
            const logs = ref([]);
            const totalLogs = ref(0);
            const currentPage = ref(1);
            const totalPages = ref(1);
            const searchQuery = ref('');
            const loading = ref(false);
            let searchTimeout = null;

            // Filters
            const isSuperAdmin = ref(<?= json_encode($userRole === 'super_admin') ?>);
            const tenantOptions = ref([]);
            const roleOptions = ref([]);
            const selectedTenant = ref('');
            const selectedRole = ref('');

            // Friendly labels for database columns to make logs human-readable
            const fieldLabels = {
                nama_lengkap: 'Nama Lengkap',
                jenis_kelamin: 'Jenis Kelamin',
                nik: 'NIK',
                no_kk: 'No. KK',
                id_angkatan: 'Angkatan',
                id_tahun_ajaran: 'Tahun Ajaran',
                id_jenjang: 'Jenjang',
                id_jurusan: 'Jurusan',
                id_kelas: 'Kelas',
                id_pendidikan: 'Pendidikan',
                nama_wali: 'Nama Wali',
                current_step: 'Tahap Registrasi',
                subdomain: 'Subdomain',
                npsn: 'NPSN',
                nama_sekolah: 'Nama Sekolah',
                alamat: 'Alamat',
                email: 'Email',
                status: 'Status Akses',
                paket_aktif: 'Paket Langganan',
                status_sinkronisasi: 'Status Sinkronisasi',
                tempat_lahir: 'Tempat Lahir',
                tanggal_lahir: 'Tanggal Lahir',
                no_telp: 'No. Telepon',
                agama: 'Agama',
                nama_ibu: 'Nama Ibu',
                nama_ayah: 'Nama Ayah',
                tenant_id: 'Sekolah',
                user_id: 'Aktor',
                id_siswa: 'Nama Siswa',
                siswa_id: 'Nama Siswa',
                role_id: 'Role/Peran',
                diverifikasi_oleh: 'Diverifikasi Oleh',
                id_guru_bk: 'Guru BK',
                id_kelas_snapshot: 'Kelas (Snapshot)',
                id_kelas_asal: 'Kelas Asal',
                id_kelas_tujuan: 'Kelas Tujuan',
                id_jurusan_lama: 'Jurusan Lama',
                id_jurusan_baru: 'Jurusan Baru',
                id_kelurahan: 'Kelurahan',
                id_tempat_lahir_ayah: 'Tempat Lahir Ayah',
                id_tempat_lahir_ibu: 'Tempat Lahir Ibu',
                id_tempat_lahir_wali: 'Tempat Lahir Wali'
            };

            const getFieldLabel = (key) => fieldLabels[key] || key;

            // Robust data comparison that filters out type mismatch noise (e.g. 3 vs "3")
            const isDifferent = (val1, val2) => {
                const str1 = val1 === null || val1 === undefined ? '' : String(val1).trim();
                const str2 = val2 === null || val2 === undefined ? '' : String(val2).trim();
                return str1 !== str2;
            };

            // Escape HTML helper
            const escapeHtml = (text) => {
                if (text === null || text === undefined) return '';
                const map = {
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#039;'
                };
                return text.toString().replace(/[&<>"']/g, function(m) { return map[m]; });
            };

            // Calculate inline changes diff (tags/pills based design)
            const getChanges = (log) => {
                if (log.action === 'INSERT') {
                    return '<span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-10 px-2 py-1"><i class="bi bi-plus-circle me-1"></i> Data Baru</span>';
                }
                if (log.action === 'DELETE') {
                    return '<span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-10 px-2 py-1"><i class="bi bi-dash-circle me-1"></i> Data Dihapus</span>';
                }
                if (log.action === 'UPDATE') {
                    try {
                        const oldData = log.old_data ? JSON.parse(log.old_data) : {};
                        const newData = log.new_data ? JSON.parse(log.new_data) : {};
                        const diff = [];
                        for (const key in newData) {
                            if (isDifferent(oldData[key], newData[key])) {
                                const oldVal = oldData[key] !== null && oldData[key] !== undefined ? oldData[key] : '';
                                const newVal = newData[key] !== null && newData[key] !== undefined ? newData[key] : '';
                                
                                const label = getFieldLabel(key);
                                const displayOld = oldVal === '' ? 'Kosong' : (oldVal.toString().length > 15 ? oldVal.toString().substring(0, 12) + '...' : oldVal);
                                const displayNew = newVal === '' ? 'Kosong' : (newVal.toString().length > 15 ? newVal.toString().substring(0, 12) + '...' : newVal);
                                
                                diff.push({ label, old: displayOld, new: displayNew });
                            }
                        }
                        
                        if (diff.length === 0) {
                            return '<span class="text-slate-600 fs-8">Tidak ada perubahan nilai pokok</span>';
                        }
                        
                        // Render up to 2 diffs inline as tag pills, wrap nicely
                        const maxShow = 2;
                        let html = '<div class="d-flex flex-wrap gap-1">';
                        diff.slice(0, maxShow).forEach(d => {
                            html += `<span class="badge bg-light text-dark border border-secondary border-opacity-20 px-2 py-1 fs-9 font-monospace" style="font-weight: 500;">
                                <span class="text-primary">${escapeHtml(d.label)}</span>: 
                                <span class="text-danger-emphasis"><s>${escapeHtml(d.old)}</s></span> ➔ 
                                <span class="text-success-emphasis fw-semibold">${escapeHtml(d.new)}</span>
                            </span>`;
                        });
                        
                        if (diff.length > maxShow) {
                            html += `<span class="badge bg-secondary bg-opacity-10 text-slate-600 border border-secondary border-opacity-10 px-2 py-1 fs-9">
                                +${diff.length - maxShow} perubahan lainnya
                            </span>`;
                        }
                        html += '</div>';
                        return html;
                    } catch (e) {
                        return '<span class="text-slate-600 fs-8">Perubahan data</span>';
                    }
                }
                return '-';
            };

            // Fetch filters options
            const fetchFilters = async () => {
                try {
                    const response = await axios.get('/dapodik-spmb/api/v1/activity-logs/filters');
                    if (response.data && response.data.success) {
                        tenantOptions.value = response.data.tenants || [];
                        roleOptions.value = response.data.roles || [];
                    }
                } catch (err) {
                    console.error('Failed to load filter options:', err);
                }
            };

            // Fetch activity logs from API
            const fetchLogs = async () => {
                loading.value = true;
                try {
                    const response = await axios.get('/dapodik-spmb/api/v1/activity-logs', {
                        params: {
                            page: currentPage.value,
                            search: searchQuery.value,
                            tenant_filter: selectedTenant.value,
                            role_filter: selectedRole.value,
                            per_page: 15
                        }
                    });

                    if (response.data && response.data.success) {
                        logs.value = response.data.data;
                        totalLogs.value = response.data.pagination.total;
                        totalPages.value = response.data.pagination.pages;
                    } else {
                        throw new Error(response.data.error || 'Gagal memuat log.');
                    }
                } catch (err) {
                    console.error(err);
                    if (window.Swal) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal Memuat Data',
                            text: err.response?.data?.error || err.message || 'Terjadi kesalahan sistem.'
                        });
                    }
                } finally {
                    loading.value = false;
                }
            };

            // Search input debounce handler
            const onSearchInput = () => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    currentPage.value = 1;
                    fetchLogs();
                }, 400);
            };

            // Trigger fetch on filters change
            const onFilterChange = () => {
                currentPage.value = 1;
                fetchLogs();
            };

            // Pagination changer
            const changePage = (page) => {
                if (page >= 1 && page <= totalPages.value) {
                    currentPage.value = page;
                    fetchLogs();
                }
            };

            // Pretty format date time
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

            // SweetAlert2 before vs after difference modal
            const showDetail = (log) => {
                const parseJSON = (str) => {
                    if (!str) return null;
                    try {
                        return JSON.parse(str);
                    } catch (e) {
                        return null;
                    }
                };

                const oldObj = parseJSON(log.old_data);
                const newObj = parseJSON(log.new_data);

                // Build a beautiful human-readable side-by-side diff table for values that changed
                let changesListHtml = '';
                if (log.action === 'UPDATE' && oldObj && newObj) {
                    changesListHtml += '<div class="card border rounded-3 p-3 mb-3 bg-light">';
                    changesListHtml += '<h6 class="fw-bold text-dark mb-2.5"><i class="bi bi-info-circle me-1 text-primary"></i> Perubahan Nilai Pokok (Diff)</h6>';
                    changesListHtml += '<div class="table-responsive"><table class="table table-sm table-striped table-bordered bg-white mb-0 fs-8 align-middle">';
                    changesListHtml += '<thead class="table-light"><tr><th style="width:200px;">Kolom Data</th><th class="text-danger">Sebelum (Old)</th><th class="text-success">Sesudah (New)</th></tr></thead><tbody>';
                    
                    let hasRealChanges = false;
                    for (const key in newObj) {
                        if (isDifferent(oldObj[key], newObj[key])) {
                            const oldVal = oldObj[key] !== null && oldObj[key] !== undefined && oldObj[key] !== '' ? oldObj[key] : 'Kosong';
                            const newVal = newObj[key] !== null && newObj[key] !== undefined && newObj[key] !== '' ? newObj[key] : 'Kosong';
                            changesListHtml += `<tr>
                                <td class="fw-semibold text-dark font-monospace">${escapeHtml(getFieldLabel(key))} <small class="text-slate-600 d-block">(${escapeHtml(key)})</small></td>
                                <td class="text-danger-emphasis">${escapeHtml(oldVal)}</td>
                                <td class="text-success-emphasis fw-semibold">${escapeHtml(newVal)}</td>
                            </tr>`;
                            hasRealChanges = true;
                        }
                    }
                    if (!hasRealChanges) {
                        changesListHtml += '<tr><td colspan="3" class="text-center text-slate-600">Tidak ada perubahan nilai pokok terdeteksi.</td></tr>';
                    }
                    changesListHtml += '</tbody></table></div></div>';
                }

                const oldFormatted = oldObj ? JSON.stringify(oldObj, null, 2) : 'Tidak ada data (Entri Baru / INSERT)';
                const newFormatted = newObj ? JSON.stringify(newObj, null, 2) : 'Tidak ada data (Hapus / DELETE)';

                Swal.fire({
                    title: `<div class="fs-6 fw-bold border-bottom pb-2 text-start text-dark">Detail Audit Trail Log</div>`,
                    html: `
                        <div class="text-start fs-8 text-slate-600">
                            <div class="row g-2 mb-3">
                                <div class="col-md-6"><strong>Pengguna:</strong> <span class="text-dark fw-semibold">${log.actor_name || 'System'}</span></div>
                                <div class="col-md-6"><strong>Peran:</strong> <span class="text-dark text-uppercase font-monospace">${log.user_role}</span></div>
                                <div class="col-md-6"><strong>Tindakan:</strong> <span class="badge ${log.action === 'INSERT' ? 'bg-success' : (log.action === 'UPDATE' ? 'bg-warning text-dark' : 'bg-danger')} font-monospace">${log.action}</span></div>
                                <div class="col-md-6"><strong>Tabel/Entitas:</strong> <span class="text-dark font-monospace">${log.table_name}</span></div>
                                <div class="col-md-6"><strong>IP Address:</strong> <span class="text-dark font-monospace">${log.ip_address}</span></div>
                                <div class="col-md-6"><strong>Waktu:</strong> <span class="text-dark">${formatDateTime(log.created_at)}</span></div>
                                <div class="col-md-12" v-if="log.nama_sekolah"><strong>Sekolah:</strong> <span class="text-dark">${log.nama_sekolah}</span></div>
                            </div>
                            
                            <!-- Modern Diff Table -->
                            ${changesListHtml}

                            <hr class="my-3">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="fw-bold text-danger mb-2"><i class="bi bi-arrow-left-circle me-1"></i> Data Sebelum (Before) - RAW JSON</div>
                                    <pre class="bg-light p-2 border rounded-3 text-dark font-monospace" style="max-height: 200px; overflow-y: auto; font-size: 0.725rem; line-height: 1.4;">${escapeHtml(oldFormatted)}</pre>
                                </div>
                                <div class="col-md-6">
                                    <div class="fw-bold text-success mb-2"><i class="bi bi-arrow-right-circle me-1"></i> Data Sesudah (After) - RAW JSON</div>
                                    <pre class="bg-light p-2 border rounded-3 text-dark font-monospace" style="max-height: 200px; overflow-y: auto; font-size: 0.725rem; line-height: 1.4;">${escapeHtml(newFormatted)}</pre>
                                </div>
                            </div>
                        </div>
                    `,
                    width: '850px',
                    showCloseButton: true,
                    confirmButtonColor: '#2563eb',
                    confirmButtonText: 'Tutup'
                });
            };

            // Run initial fetch
            onMounted(() => {
                fetchFilters();
                fetchLogs();
            });

            return {
                logs,
                totalLogs,
                currentPage,
                totalPages,
                searchQuery,
                loading,
                isSuperAdmin,
                tenantOptions,
                roleOptions,
                selectedTenant,
                selectedRole,
                getChanges,
                onSearchInput,
                onFilterChange,
                changePage,
                formatDateTime,
                showDetail
            };
        }
    });
}
</script>
