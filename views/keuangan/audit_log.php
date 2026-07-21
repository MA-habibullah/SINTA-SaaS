<div id="audit-log-app" v-cloak class="container-fluid px-4 py-4">
    <!-- Header -->
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h2 class="fw-bold text-slate-800 mb-1">
                <i class="bi bi-shield-check text-rose-600 me-2"></i> Audit Trail & Log Security
            </h2>
            <p class="text-muted mb-0">Inspeksi jejak audit aktivitas petugas/pengguna, pembayaran, pembatalan (void), dan perubahan data sistem.</p>
        </div>
        <span class="badge bg-rose-100 text-rose-700 px-3 py-2 rounded-pill font-monospace fs-7 border border-rose-200">
            <i class="bi bi-lock-fill me-1"></i> Khusus Super Admin Platform
        </span>
    </div>

    <!-- Ringkasan Statistik Audit Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 border-start border-4 border-primary">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted fs-8 fw-semibold text-uppercase">Total Aktivitas Audit</div>
                        <h3 class="fw-bold text-slate-800 mb-0 mt-1">{{ formatNumber(metrics.total_log || 0) }}</h3>
                    </div>
                    <div class="bg-primary-subtle text-primary p-3 rounded-4">
                        <i class="bi bi-journal-text fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 border-start border-4 border-success">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted fs-8 fw-semibold text-uppercase">Transaksi Pembayaran</div>
                        <h3 class="fw-bold text-success mb-0 mt-1">{{ formatNumber(metrics.total_pembayaran || 0) }}</h3>
                    </div>
                    <div class="bg-success-subtle text-success p-3 rounded-4">
                        <i class="bi bi-cash-stack fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 border-start border-4 border-danger">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted fs-8 fw-semibold text-uppercase">Pembatalan (Void)</div>
                        <h3 class="fw-bold text-danger mb-0 mt-1">{{ formatNumber(metrics.total_void || 0) }}</h3>
                    </div>
                    <div class="bg-danger-subtle text-danger p-3 rounded-4">
                        <i class="bi bi-arrow-counterclockwise fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 border-start border-4 border-warning">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted fs-8 fw-semibold text-uppercase">Modifikasi & Hapus Data</div>
                        <h3 class="fw-bold text-warning mb-0 mt-1">{{ formatNumber(metrics.total_modifikasi || 0) }}</h3>
                    </div>
                    <div class="bg-warning-subtle text-warning p-3 rounded-4">
                        <i class="bi bi-pencil-square fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Bar Audit -->
    <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
        <div class="row g-3 align-items-end">
            <!-- Filter Tenant (Sekolah) -->
            <div class="col-md-3">
                <label class="form-label fw-semibold text-slate-700 fs-8 mb-1">Filter Sekolah / Tenant</label>
                <select class="form-select border-slate-200" v-model="filterTenantId" @change="fetchAuditLogs(1)" style="height: 42px;">
                    <option value="">-- Semua Sekolah (Tenant) --</option>
                    <option v-for="t in tenantsList" :key="t.id" :value="t.id">{{ t.nama_sekolah }}</option>
                </select>
            </div>

            <!-- Filter Jenis Aksi -->
            <div class="col-md-2">
                <label class="form-label fw-semibold text-slate-700 fs-8 mb-1">Jenis Aksi</label>
                <select class="form-select border-slate-200" v-model="filterAksi" @change="fetchAuditLogs(1)" style="height: 42px;">
                    <option value="">-- Semua Aksi --</option>
                    <option value="PAYMENT_SPP">PAYMENT_SPP (Pembayaran)</option>
                    <option value="VOID_PAYMENT">VOID_PAYMENT (Pembatalan)</option>
                    <option value="GENERATE_TAGIHAN">GENERATE_TAGIHAN (Generate)</option>
                    <option value="EDIT_TAGIHAN_NOMINAL">EDIT_TAGIHAN (Edit Nominal)</option>
                    <option value="DELETE_TAGIHAN">DELETE_TAGIHAN (Hapus)</option>
                    <option value="CREATE_PPDB_TAGIHAN">CREATE_PPDB_TAGIHAN (PPDB)</option>
                </select>
            </div>

            <!-- Filter Tanggal Mulai -->
            <div class="col-md-2">
                <label class="form-label fw-semibold text-slate-700 fs-8 mb-1">Dari Tanggal</label>
                <input type="date" class="form-control border-slate-200" v-model="filterDateFrom" @change="fetchAuditLogs(1)" style="height: 42px;">
            </div>

            <!-- Filter Tanggal Akhir -->
            <div class="col-md-2">
                <label class="form-label fw-semibold text-slate-700 fs-8 mb-1">Sampai Tanggal</label>
                <input type="date" class="form-control border-slate-200" v-model="filterDateTo" @change="fetchAuditLogs(1)" style="height: 42px;">
            </div>

            <!-- Search Field -->
            <div class="col-md-3">
                <label class="form-label fw-semibold text-slate-700 fs-8 mb-1">Pencarian Kata Kunci</label>
                <div class="input-group">
                    <input type="text" class="form-control border-slate-200" v-model="searchQuery" @keyup.enter="fetchAuditLogs(1)" placeholder="Cari nama petugas, kwitansi, ID..." style="height: 42px;">
                    <button class="btn btn-primary px-3" type="button" @click="fetchAuditLogs(1)">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Tabel Audit Log -->
    <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
        <div class="card-header bg-white py-3 px-4 border-bottom d-flex align-items-center justify-content-between">
            <h6 class="fw-bold text-slate-800 mb-0">
                <i class="bi bi-table text-primary me-2"></i> Log Security System
            </h6>
            <div class="d-flex align-items-center gap-2">
                <div class="d-flex align-items-center gap-1">
                    <label class="form-label mb-0 fs-8 fw-semibold text-slate-600 text-nowrap">Tampilkan:</label>
                    <select class="form-select form-select-sm border-slate-200" v-model.number="pageSize" @change="fetchAuditLogs(1)" style="width: 130px; height: 32px;">
                        <option :value="25">25 baris</option>
                        <option :value="50">50 baris</option>
                        <option :value="100">100 baris</option>
                    </select>
                </div>
                <button class="btn btn-sm btn-outline-secondary rounded-3" @click="fetchAuditLogs(pagination.current_page)" title="Refresh Data">
                    <i class="bi bi-arrow-clockwise me-1"></i> Muat Ulang
                </button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Waktu</th>
                        <th>Sekolah (Tenant)</th>
                        <th>Petugas / User</th>
                        <th>Jenis Aksi</th>
                        <th>Tabel Target</th>
                        <th>Keterangan / Aktivitas</th>
                        <th class="text-center pe-4">Detail Diff</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="loadingLogs">
                        <td colspan="7" class="text-center py-5 text-muted">
                            <span class="spinner-border spinner-border-sm me-2"></span> Memuat log aktivitas keamanan...
                        </td>
                    </tr>
                    <tr v-else-if="logs.length === 0">
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="bi bi-journal-x fs-1 d-block mb-2 text-slate-300"></i>
                            Tidak ada catatan audit log yang cocok dengan filter.
                        </td>
                    </tr>
                    <tr v-else v-for="log in logs" :key="log.id">
                        <td class="ps-4 text-nowrap fs-8 font-monospace text-slate-600">
                            {{ formatDate(log.created_at) }}
                        </td>
                        <td>
                            <span class="fw-semibold text-slate-800 fs-7 d-block">{{ log.nama_sekolah || 'Global System' }}</span>
                            <span class="text-muted fs-8 font-monospace">{{ log.tenant_id ? log.tenant_id.substring(0, 8) + '...' : '' }}</span>
                        </td>
                        <td>
                            <div class="fw-bold text-slate-800 fs-7">{{ log.nama_user || 'System / Unknown' }}</div>
                            <span class="badge bg-slate-100 text-slate-700 font-monospace fs-8">{{ log.role_name || log.username || 'User' }}</span>
                        </td>
                        <td>
                            <span class="badge" :class="getAksiBadgeClass(log.aksi)">
                                {{ log.aksi }}
                            </span>
                        </td>
                        <td class="font-monospace fs-8 text-slate-600">
                            {{ log.tabel_target }}
                        </td>
                        <td class="fs-7 text-slate-700">
                            {{ log.keterangan || '-' }}
                        </td>
                        <td class="text-center pe-4">
                            <button v-if="log.data_sebelum || log.data_sesudah" class="btn btn-sm btn-outline-info py-0 px-2 fs-8 rounded-2 d-inline-flex align-items-center gap-1" @click="showDetailModal(log)">
                                <i class="bi bi-eye"></i> Perubahan
                            </button>
                            <span v-else class="text-muted fs-8">-</span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination Footer -->
        <div class="card-footer bg-white py-3 px-4 border-top d-flex align-items-center justify-content-between">
            <div class="fs-8 text-muted">
                Menampilkan <strong>{{ logs.length }}</strong> dari <strong>{{ pagination.total_records }}</strong> log audit (Halaman {{ pagination.current_page }} dari {{ pagination.total_pages || 1 }}).
            </div>
            <nav aria-label="Audit pagination" v-if="pagination.total_pages > 1">
                <ul class="pagination pagination-sm mb-0">
                    <li class="page-item" :class="{ disabled: pagination.current_page === 1 }">
                        <button class="page-link" @click="fetchAuditLogs(pagination.current_page - 1)">Sebelumnya</button>
                    </li>
                    <li class="page-item" v-for="p in pagination.total_pages" :key="p" :class="{ active: p === pagination.current_page }">
                        <button class="page-link" @click="fetchAuditLogs(p)">{{ p }}</button>
                    </li>
                    <li class="page-item" :class="{ disabled: pagination.current_page === pagination.total_pages }">
                        <button class="page-link" @click="fetchAuditLogs(pagination.current_page + 1)">Selanjutnya</button>
                    </li>
                </ul>
            </nav>
        </div>
    </div>

    <!-- Modal Detail Perubahan Data (JSON Diff) -->
    <div v-if="showModal" class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5); z-index: 1055;" @click.self="showModal = false">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content shadow-lg border-0 rounded-4">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold text-slate-800">
                        <i class="bi bi-code-square text-info me-2"></i> Detail Perubahan Data (Audit JSON Diff)
                    </h5>
                    <button type="button" class="btn-close" @click="showModal = false" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4" v-if="selectedLog">
                    <div class="p-3 bg-light rounded-3 mb-3 fs-8">
                        <div class="row g-2">
                            <div class="col-md-6"><strong>ID Log:</strong> #{{ selectedLog.id }}</div>
                            <div class="col-md-6"><strong>Waktu:</strong> {{ formatDate(selectedLog.created_at) }}</div>
                            <div class="col-md-6"><strong>Aksi:</strong> <span class="badge" :class="getAksiBadgeClass(selectedLog.aksi)">{{ selectedLog.aksi }}</span></div>
                            <div class="col-md-6"><strong>Petugas:</strong> {{ selectedLog.nama_user }} ({{ selectedLog.role_name }})</div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <!-- Data Sebelum -->
                        <div class="col-md-6">
                            <h6 class="fw-bold text-slate-700 fs-8 text-uppercase mb-2"><i class="bi bi-arrow-left-circle me-1 text-danger"></i> Data Sebelum (Before)</h6>
                            <pre class="bg-dark text-light p-3 rounded-3 fs-8 font-monospace overflow-auto" style="max-height: 250px;">{{ formatJson(selectedLog.data_sebelum) }}</pre>
                        </div>
                        <!-- Data Sesudah -->
                        <div class="col-md-6">
                            <h6 class="fw-bold text-slate-700 fs-8 text-uppercase mb-2"><i class="bi bi-arrow-right-circle me-1 text-success"></i> Data Sesudah (After)</h6>
                            <pre class="bg-dark text-light p-3 rounded-3 fs-8 font-monospace overflow-auto" style="max-height: 250px;">{{ formatJson(selectedLog.data_sesudah) }}</pre>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-secondary fw-semibold" @click="showModal = false">Tutup</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    const { createApp, ref, onMounted } = Vue;

    createApp({
        setup() {
            const logs = ref([]);
            const metrics = ref({});
            const tenantsList = ref([]);
            const loadingLogs = ref(false);

            const filterTenantId = ref('');
            const filterAksi = ref('');
            const filterDateFrom = ref('');
            const filterDateTo = ref('');
            const searchQuery = ref('');
            const pageSize = ref(25);

            const pagination = ref({
                current_page: 1,
                page_size: 25,
                total_records: 0,
                total_pages: 1
            });

            const showModal = ref(false);
            const selectedLog = ref(null);

            const fetchTenants = async () => {
                try {
                    const response = await fetch('/SINTA-SaaS/api/v1/keuangan/tenants');
                    const res = await response.json();
                    if (res.success) {
                        tenantsList.value = res.data || [];
                    }
                } catch (e) {}
            };

            const fetchAuditLogs = async (page = 1) => {
                loadingLogs.value = true;
                try {
                    const params = new URLSearchParams({
                        page: page,
                        page_size: pageSize.value,
                        tenant_id: filterTenantId.value,
                        aksi: filterAksi.value,
                        date_from: filterDateFrom.value,
                        date_to: filterDateTo.value,
                        q: searchQuery.value
                    });

                    const response = await fetch('/SINTA-SaaS/api/v1/keuangan/audit-log?' + params.toString());
                    const res = await response.json();
                    if (res.success) {
                        logs.value = res.data || [];
                        metrics.value = res.metrics || {};
                        pagination.value = res.pagination || pagination.value;
                    } else {
                        alert(res.error || 'Gagal memuat data log audit.');
                    }
                } catch (e) {
                    alert('Terjadi kesalahan jaringan saat memuat audit log.');
                } finally {
                    loadingLogs.value = false;
                }
            };

            const showDetailModal = (log) => {
                selectedLog.value = log;
                showModal.value = true;
            };

            const formatNumber = (val) => {
                return new Intl.NumberFormat('id-ID').format(val || 0);
            };

            const formatDate = (dateStr) => {
                if (!dateStr) return '-';
                const d = new Date(dateStr);
                return d.toLocaleString('id-ID', {
                    day: 'numeric',
                    month: 'short',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit'
                });
            };

            const formatJson = (jsonStr) => {
                if (!jsonStr) return 'Tidak Ada Data';
                try {
                    const obj = typeof jsonStr === 'string' ? JSON.parse(jsonStr) : jsonStr;
                    return JSON.stringify(obj, null, 2);
                } catch (e) {
                    return jsonStr;
                }
            };

            const getAksiBadgeClass = (aksi) => {
                if (!aksi) return 'bg-secondary';
                if (aksi === 'PAYMENT_SPP') return 'bg-success';
                if (aksi === 'VOID_PAYMENT') return 'bg-danger text-white';
                if (aksi.includes('DELETE')) return 'bg-rose-600 text-white';
                if (aksi.includes('EDIT') || aksi.includes('UPDATE')) return 'bg-warning text-dark';
                if (aksi.includes('GENERATE') || aksi.includes('CREATE')) return 'bg-info text-dark';
                return 'bg-secondary';
            };

            onMounted(() => {
                fetchTenants();
                fetchAuditLogs(1);
            });

            return {
                logs,
                metrics,
                tenantsList,
                loadingLogs,
                filterTenantId,
                filterAksi,
                filterDateFrom,
                filterDateTo,
                searchQuery,
                pageSize,
                pagination,
                showModal,
                selectedLog,
                fetchAuditLogs,
                showDetailModal,
                formatNumber,
                formatDate,
                formatJson,
                getAksiBadgeClass
            };
        }
    }).mount('#audit-log-app');
})();
</script>
