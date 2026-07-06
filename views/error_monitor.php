<?php
/**
 * View: Error Monitor — System Debugger
 * Akses: Super Admin Only
 */
?>

<style>
    [v-cloak] { display: none !important; }
    @keyframes spin { to { transform: rotate(360deg); } }
    .fs-9 { font-size: 0.725rem !important; }

    /* High contrast color overrides for WCAG compliance (ratio > 4.5:1 on light backgrounds) */
    .text-slate-600 {
        color: #475569 !important;
    }
    .text-slate-700 {
        color: #334155 !important;
    }
    #errorMonitorApp .text-muted,
    #errorMonitorApp .text-secondary,
    #modalErrorTrace .text-muted,
    #modalErrorTrace .text-secondary,
    .swal2-container .text-muted,
    .swal2-container .text-secondary {
        color: #475569 !important;
    }
    #errorMonitorApp .bg-light .text-primary,
    #modalErrorTrace .bg-light .text-primary {
        color: #1d4ed8 !important;
    }
</style>

<!-- ============================================================
     ROOT VUE APP: Wrapper mencakup SELURUH halaman termasuk
     header + tombol Clear All agar semua binding Vue berfungsi
     ============================================================ -->
<div id="errorMonitorApp" v-cloak>

    <!-- PAGE HEADER -->
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-start pt-2 pb-3 mb-4 border-bottom gap-3">
        <div>
            <h2 class="fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                <span class="d-inline-flex align-items-center justify-content-center rounded-3 shadow-sm"
                      style="width:40px;height:40px;background:linear-gradient(135deg,#fee2e2,#fecaca);">
                    <i class="bi bi-bug-fill text-danger fs-5"></i>
                </span>
                Error Monitor
            </h2>
            <p class="text-muted fs-7 mb-0">
                Pantau semua error PHP/SQL yang tertangkap secara otomatis.
                <span class="badge bg-danger-subtle text-danger border border-danger border-opacity-25 ms-1 fw-semibold fs-9">
                    <i class="bi bi-lock-fill me-1"></i>Super Admin Only
                </span>
            </p>
        </div>

        <!-- Tombol Clear All — DALAM scope Vue agar binding berfungsi -->
        <button
            id="btn-clear-all-errors"
            class="btn btn-outline-danger rounded-3 px-4 py-2 fs-7 fw-semibold d-flex align-items-center gap-2 flex-shrink-0"
            @click="confirmClearAll"
            :disabled="loadingClear || totalErrors === 0"
        >
            <span v-if="loadingClear" class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
            <i v-else class="bi bi-trash3-fill"></i>
            <span>Clear All Logs</span>
            <span class="badge rounded-pill ms-1"
                  :class="totalErrors > 0 ? 'bg-danger' : 'bg-secondary'">
                {{ totalErrors }}
            </span>
        </button>
    </div>

    <!-- ============================================================
         STATISTIK RINGKASAN PER ERROR LEVEL
         ============================================================ -->
    <div class="row g-3 mb-4">
        <div v-for="stat in stats" :key="stat.error_level" class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 h-100" :class="levelCardClass(stat.error_level)">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 p-2 d-flex align-items-center justify-content-center flex-shrink-0"
                         :class="levelIconBg(stat.error_level)"
                         style="width:44px;height:44px;">
                        <i :class="levelIcon(stat.error_level)" class="fs-5"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="fs-3 fw-bold text-dark lh-1">{{ stat.jumlah }}</div>
                        <div class="fs-9 text-muted text-truncate font-monospace mt-1"
                             style="max-width:110px;" :title="stat.error_level">
                            {{ stat.error_level }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Skeleton loading cards -->
        <template v-if="loading && stats.length === 0">
            <div class="col-6 col-md-3" v-for="n in 4" :key="'sk-'+n">
                <div class="card border-0 shadow-sm rounded-4 p-3 h-100">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-3 bg-light placeholder-glow" style="width:44px;height:44px;"></div>
                        <div class="flex-grow-1">
                            <div class="placeholder-glow mb-1"><span class="placeholder col-4 rounded"></span></div>
                            <div class="placeholder-glow"><span class="placeholder col-8 rounded" style="height:0.7rem;"></span></div>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <!-- Alert sukses jika tidak ada error sama sekali -->
        <div v-if="!loading && stats.length === 0" class="col-12">
            <div class="alert border-0 rounded-4 d-flex align-items-center gap-3 mb-0 shadow-sm py-3 px-4"
                 style="background:linear-gradient(135deg,#f0fdf4,#dcfce7);border-left:4px solid #22c55e !important;">
                <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                     style="width:40px;height:40px;background:#22c55e;">
                    <i class="bi bi-check-lg text-white fs-5"></i>
                </div>
                <div>
                    <div class="fw-bold text-success fs-7">Semua Sistem Berjalan Normal!</div>
                    <div class="fs-8 text-muted">Tidak ada error PHP/SQL yang tertangkap. Platform berjalan dengan sempurna.</div>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================
         FILTER TOOLBAR
         ============================================================ -->
    <div class="card border-0 shadow-sm rounded-4 p-3 mb-4" style="background:#fff;">
        <div class="row g-2 align-items-center">
            <!-- Search -->
            <div class="col-12 col-md-5">
                <div class="position-relative">
                    <label for="errSearchQuery" class="visually-hidden">Cari Log Error</label>
                    <i class="bi bi-search position-absolute text-slate-600"
                       style="left:14px;top:50%;transform:translateY(-50%);font-size:.8rem;pointer-events:none;"></i>
                    <input type="text"
                           id="errSearchQuery"
                           name="search_query"
                           class="form-control rounded-pill ps-5 fs-8 border-0 bg-light"
                           style="height:40px;"
                           v-model="searchQuery"
                           @input="onSearch"
                           placeholder="Cari pesan error, file, URL...">
                </div>
            </div>

            <!-- Level Filter -->
            <div class="col-6 col-md-3">
                <label for="filterErrorLevel" class="visually-hidden">Filter Level Error</label>
                <select id="filterErrorLevel"
                        name="level_filter"
                        class="form-select rounded-pill fs-8 border-0 bg-light font-monospace"
                        style="height:40px;"
                        v-model="levelFilter"
                        @change="loadErrors(1)">
                    <option value="">Semua Level</option>
                    <option v-for="s in stats" :key="s.error_level" :value="s.error_level">
                        {{ s.error_level }} ({{ s.jumlah }})
                    </option>
                </select>
            </div>

            <!-- Per Page -->
            <div class="col-6 col-md-2">
                <label for="errorsPerPage" class="visually-hidden">Baris per Halaman</label>
                <select id="errorsPerPage"
                        name="per_page"
                        class="form-select rounded-pill fs-8 border-0 bg-light"
                        style="height:40px;"
                        v-model="perPage"
                        @change="loadErrors(1)">
                    <option value="10">10 / hal</option>
                    <option value="20">20 / hal</option>
                    <option value="50">50 / hal</option>
                </select>
            </div>

            <!-- Refresh & Counter -->
            <div class="col-12 col-md-2 d-flex align-items-center justify-content-end gap-2">
                <span class="text-muted fs-9 d-none d-md-inline">
                    {{ totalErrors }} entri
                </span>
                <button class="btn btn-light border rounded-pill px-3 fs-8 d-flex align-items-center gap-2"
                        style="height:40px;"
                        @click="loadErrors(currentPage)"
                        :disabled="loading">
                    <i class="bi bi-arrow-clockwise" :style="loading ? 'animation:spin 1s linear infinite;display:inline-block;' : ''"></i>
                    Refresh
                </button>
            </div>
        </div>
    </div>

    <!-- ============================================================
         TABEL ERROR LOG
         ============================================================ -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-5">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr class="fs-8 text-muted" style="background:#f8fafc;border-bottom:2px solid #e2e8f0;">
                        <th class="ps-4 py-3 fw-semibold" style="width:155px;">Waktu</th>
                        <th class="py-3 fw-semibold" style="width:130px;">Level</th>
                        <th class="py-3 fw-semibold">Pesan Error</th>
                        <th class="py-3 fw-semibold" style="width:190px;">File : Baris</th>
                        <th class="py-3 fw-semibold" style="width:160px;">Request</th>
                        <th class="py-3 fw-semibold text-center" style="width:110px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Loading shimmer -->
                    <tr v-if="loading">
                        <td colspan="6" class="text-center py-5">
                            <div class="d-flex flex-column align-items-center gap-2 text-muted">
                                <div class="spinner-border text-primary" role="status" style="width:2rem;height:2rem;"></div>
                                <span class="fs-8">Memuat data error log...</span>
                            </div>
                        </td>
                    </tr>

                    <!-- Empty state -->
                    <tr v-else-if="errors.length === 0">
                        <td colspan="6" class="text-center py-5">
                            <div class="d-flex flex-column align-items-center gap-2">
                                <div class="rounded-circle d-flex align-items-center justify-content-center mb-1"
                                     style="width:56px;height:56px;background:#f0fdf4;">
                                    <i class="bi bi-shield-check fs-2 text-success"></i>
                                </div>
                                <span class="fs-7 fw-semibold text-success">Tidak ada error ditemukan</span>
                                <span class="fs-8 text-muted" v-if="searchQuery || levelFilter">
                                    Coba hapus filter untuk melihat semua log.
                                </span>
                            </div>
                        </td>
                    </tr>

                    <!-- Data rows -->
                    <tr v-else
                        v-for="err in errors"
                        :key="err.id"
                        class="fs-8 border-bottom"
                        style="border-color:#f1f5f9 !important;">
                        <!-- Waktu -->
                        <td class="ps-4 text-muted font-monospace" style="white-space:nowrap;font-size:0.73rem;">
                            {{ formatDateTime(err.created_at) }}
                        </td>
                        <!-- Level Badge -->
                        <td>
                            <span class="badge fw-semibold font-monospace px-2 py-1"
                                  style="font-size:0.7rem;"
                                  :class="levelBadgeClass(err.error_level)">
                                {{ err.error_level }}
                            </span>
                        </td>
                        <!-- Pesan -->
                        <td style="max-width:0;">
                            <div class="text-dark fw-semibold text-truncate" style="max-width:340px;">
                                {{ err.message }}
                            </div>
                            <div class="mt-1 d-flex align-items-center gap-1 flex-wrap">
                                <span v-if="err.nama_sekolah"
                                      class="badge bg-light text-dark border fs-9">
                                    <i class="bi bi-building me-1"></i>{{ err.nama_sekolah }}
                                </span>
                            </div>
                        </td>
                        <!-- File & Baris -->
                        <td>
                            <div class="d-flex align-items-center gap-1 flex-wrap">
                                <code class="text-primary d-block" style="font-size:0.7rem;word-break:break-all;max-width:160px;" :title="err.file">
                                    {{ shortenPath(err.file) }}
                                </code>
                                <span class="badge text-warning-emphasis fw-semibold flex-shrink-0"
                                      style="background:#fefce8;border:1px solid #fde68a;font-size:0.68rem;">
                                    L.{{ err.line || '?' }}
                                </span>
                            </div>
                        </td>
                        <!-- Request URL -->
                        <td>
                            <div class="d-flex align-items-center gap-1 mb-1">
                                <span class="badge bg-slate-100 text-slate-700 border fs-9 font-monospace flex-shrink-0"
                                      style="background:#f1f5f9;color:#475569;border-color:#e2e8f0;">
                                    {{ err.request_method }}
                                </span>
                                <code class="text-muted text-truncate" style="font-size:0.7rem;max-width:100px;" :title="err.request_url">
                                    {{ shortenUrl(err.request_url) }}
                                </code>
                            </div>
                            <div class="text-muted font-monospace" style="font-size:0.68rem;">{{ err.ip_address }}</div>
                        </td>
                        <!-- Aksi -->
                        <td class="text-center pe-3">
                            <div class="d-flex gap-1 justify-content-center">
                                <button class="btn btn-sm btn-primary rounded-3 px-2 py-1 shadow-sm"
                                        style="font-size:0.72rem;"
                                        @click="openTraceModal(err)"
                                        title="Lihat Stack Trace">
                                    <i class="bi bi-bug me-1"></i>Trace
                                </button>
                                <button class="btn btn-sm btn-outline-danger rounded-3 px-2 py-1"
                                        style="font-size:0.72rem;"
                                        @click="deleteOne(err.id)"
                                        title="Hapus log ini">
                                    <i class="bi bi-trash3"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination Footer -->
        <div class="d-flex align-items-center justify-content-between px-4 py-3 border-top"
             style="background:#f8fafc;"
             v-if="totalPages > 0">
            <div class="text-muted fs-8">
                <span v-if="totalErrors > 0">
                    Halaman <strong class="text-dark">{{ currentPage }}</strong> / <strong class="text-dark">{{ totalPages }}</strong>
                    &nbsp;·&nbsp; Total <strong class="text-dark">{{ totalErrors }}</strong> entri
                </span>
                <span v-else class="text-muted fs-8">Tidak ada data</span>
            </div>
            <div class="d-flex gap-2" v-if="totalPages > 1">
                <button class="btn btn-sm btn-light border rounded-3 px-3"
                        @click="loadErrors(currentPage - 1)"
                        :disabled="currentPage <= 1 || loading">
                    <i class="bi bi-chevron-left"></i>
                </button>
                <span class="btn btn-sm btn-primary rounded-3 px-3 pe-none" style="cursor:default;">
                    {{ currentPage }}
                </span>
                <button class="btn btn-sm btn-light border rounded-3 px-3"
                        @click="loadErrors(currentPage + 1)"
                        :disabled="currentPage >= totalPages || loading">
                    <i class="bi bi-chevron-right"></i>
                </button>
            </div>
        </div>
    </div>

</div><!-- /errorMonitorApp -->


<!-- ============================================================
     MODAL: STACK TRACE DETAIL
     (Berada di luar div#app karena Bootstrap Modal memerlukannya di body-level)
     Binding Vue diatur melalui data yang diambil dari instance global
     ============================================================ -->
<div class="modal fade" id="modalErrorTrace" tabindex="-1" aria-labelledby="modalErrorTraceLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg" style="border-radius:16px;">
            <!-- Modal Header -->
            <div class="modal-header px-4 pt-4 pb-3" style="border-bottom:1px solid #f1f5f9;">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0"
                         style="width:42px;height:42px;background:linear-gradient(135deg,#fee2e2,#fecaca);">
                        <i class="bi bi-bug-fill text-danger fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-dark mb-0 fs-6" id="modalErrorTraceLabel">
                            Detail Stack Trace
                        </h5>
                        <p class="text-muted fs-8 mb-0 mt-0 font-monospace" id="modal-trace-subtitle">
                            Memuat...
                        </p>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>

            <!-- Modal Body -->
            <div class="modal-body px-4 py-3" id="modal-trace-body">
                <!-- Diisi via JS saat modal dibuka -->
                <div class="text-center py-4 text-muted fs-8">
                    <div class="spinner-border spinner-border-sm text-primary me-2"></div>
                    Memuat detail...
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="modal-footer px-4 py-3" style="border-top:1px solid #f1f5f9;background:#f8fafc;border-radius:0 0 16px 16px;">
                <button type="button" class="btn btn-light rounded-3 fw-semibold px-4" data-bs-dismiss="modal">
                    <i class="bi bi-x-lg me-1"></i>Tutup
                </button>
                <button type="button" class="btn btn-outline-danger rounded-3 fw-semibold px-4" id="btn-modal-delete-error">
                    <i class="bi bi-trash3 me-1"></i>Hapus Log Ini
                </button>
            </div>
        </div>
    </div>
</div>


<!-- ============================================================
     VUE 3 SCRIPT
     ============================================================ -->
<script>
{
    window.VueAppRegistry.register('#errorMonitorApp', {
        data() {
            return {
                errors:        [],
                stats:         [],
                loading:       false,
                loadingClear:  false,
                searchQuery:   '',
                levelFilter:   '',
                perPage:       20,
                currentPage:   1,
                totalErrors:   0,
                totalPages:    1,
                searchTimer:   null,
                // Modal dikelola via Vanilla JS (di luar scope Vue karena Bootstrap modal)
                _traceModal:   null,
                _currentErrId: null,
            };
        },

        mounted() {
            // Inisialisasi Bootstrap Modal
            this._traceModal = new bootstrap.Modal(document.getElementById('modalErrorTrace'));

            // Bind tombol delete di footer modal (overwrite event handler to avoid duplication under Turbo)
            document.getElementById('btn-modal-delete-error').onclick = () => {
                if (this._currentErrId) {
                    this.deleteOne(this._currentErrId);
                    this._traceModal.hide();
                }
            };

            this.loadErrors(1);
        },

        methods: {
            // ─── Data Fetching ─────────────────────────────────────────
            async loadErrors(page = 1) {
                this.loading     = true;
                this.currentPage = page;
                try {
                    const params = new URLSearchParams({
                        page:         page,
                        per_page:     this.perPage,
                        search:       this.searchQuery,
                        level_filter: this.levelFilter,
                    });
                    const res  = await axios.get(`/SINTA-SaaS/api/v1/error-monitor?${params}`);
                    const data = res.data;

                    this.errors      = data.data              || [];
                    this.stats       = data.stats             || [];
                    this.totalErrors = (data && data.pagination && data.pagination.total) || 0;
                    this.totalPages  = (data && data.pagination && data.pagination.pages) || 1;
                } catch {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal Memuat',
                        text: 'Tidak dapat memuat data error log. Periksa koneksi server.',
                        confirmButtonColor: '#2563eb'
                    });
                } finally {
                    this.loading = false;
                }
            },

            onSearch() {
                clearTimeout(this.searchTimer);
                this.searchTimer = setTimeout(() => this.loadErrors(1), 400);
            },

            // ─── Modal Stack Trace ─────────────────────────────────────
            openTraceModal(err) {
                this._currentErrId = err.id;

                // Isi subtitle
                document.getElementById('modal-trace-subtitle').textContent =
                    `${err.error_level} — ${this.shortenPath(err.file)} (Baris ${err.line || '?'})`;

                // Build HTML konten modal secara Vanilla JS (aman dari XSS via escapeHtml)
                const esc = (s) => String(s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');

                let traceHtml = '';
                try {
                    const frames = JSON.parse(err.trace || '[]');
                    if (Array.isArray(frames) && frames.length > 0) {
                        const rows = frames.map((f, i) => `
                            <tr>
                                <td class="text-muted ps-3">${i}</td>
                                <td>
                                    ${f.class ? `<span class="text-info">${esc(f.class)}</span>` : ''}
                                    ${f.type  ? `<span class="text-muted">${esc(f.type)}</span>`  : ''}
                                    <span class="text-warning fw-semibold">${esc(f.function)}</span>
                                    <span class="text-muted">()</span>
                                </td>
                                <td class="text-muted text-truncate" style="max-width:220px;" title="${esc(f.file)}">
                                    ${esc(this.shortenPath(f.file))}
                                </td>
                                <td class="text-center pe-3">
                                    ${f.line
                                        ? `<span class="badge" style="background:#fefce8;color:#854d0e;border:1px solid #fde68a;">${f.line}</span>`
                                        : '<span class="text-muted">-</span>'}
                                </td>
                            </tr>`).join('');

                        traceHtml = `
                            <div class="border rounded-3 overflow-hidden">
                                <table class="table table-sm table-striped align-middle mb-0 font-monospace" style="font-size:0.72rem;">
                                    <thead class="table-dark">
                                        <tr>
                                            <th class="ps-3" style="width:36px;">#</th>
                                            <th>Fungsi / Metode</th>
                                            <th style="width:230px;">File</th>
                                            <th class="text-center pe-3" style="width:60px;">Baris</th>
                                        </tr>
                                    </thead>
                                    <tbody>${rows}</tbody>
                                </table>
                            </div>`;
                    } else {
                        traceHtml = `<pre class="bg-dark text-light p-3 rounded-3 mb-0" style="font-size:0.72rem;max-height:280px;overflow-y:auto;">${esc(err.trace)}</pre>`;
                    }
                } catch {
                    traceHtml = `<pre class="bg-dark text-light p-3 rounded-3 mb-0" style="font-size:0.72rem;max-height:280px;overflow-y:auto;">${esc(err.trace)}</pre>`;
                }

                // Parse Context jika ada
                let contextHtml = '';
                if (err.context) {
                    try {
                        const ctx = typeof err.context === 'string' ? JSON.parse(err.context) : err.context;
                        // Format ke string JSON yang rapi
                        const ctxString = JSON.stringify(ctx, null, 2);
                        contextHtml = `
                        <div class="mb-3">
                            <div class="fs-8 fw-bold text-muted mb-2 text-uppercase d-flex align-items-center gap-1" style="letter-spacing:.5px;">
                                <i class="bi bi-info-square-fill text-info"></i> Context (Telemetry)
                            </div>
                            <pre class="bg-dark text-light p-3 rounded-3 mb-0 font-monospace" style="font-size:0.72rem;max-height:250px;overflow-y:auto;">${esc(ctxString)}</pre>
                        </div>
                        `;
                    } catch (e) {
                        contextHtml = `<div class="alert alert-warning py-2 fs-8">Gagal memproses detail context.</div>`;
                    }
                }

                document.getElementById('modal-trace-body').innerHTML = `
                    <!-- Metadata Row -->
                    <div class="row g-2 mb-3">
                        <div class="col-md-4">
                            <div class="rounded-3 p-3 h-100" style="background:#f8fafc;border:1px solid #e2e8f0;">
                                <div class="fs-9 text-muted fw-semibold text-uppercase mb-1" style="letter-spacing:.5px;">Level</div>
                                <span class="badge fw-semibold font-monospace px-2 py-1 ${this.levelBadgeClass(err.error_level)}">${esc(err.error_level)}</span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="rounded-3 p-3 h-100" style="background:#f8fafc;border:1px solid #e2e8f0;">
                                <div class="fs-9 text-muted fw-semibold text-uppercase mb-1" style="letter-spacing:.5px;">Waktu</div>
                                <div class="fs-8 font-monospace text-dark">${esc(this.formatDateTime(err.created_at))}</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="rounded-3 p-3 h-100" style="background:#f8fafc;border:1px solid #e2e8f0;">
                                <div class="fs-9 text-muted fw-semibold text-uppercase mb-1" style="letter-spacing:.5px;">Request</div>
                                <div class="fs-9 font-monospace text-dark text-break">
                                    <span class="badge bg-secondary me-1">${esc(err.request_method)}</span>${esc(err.request_url)}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pesan Error -->
                    <div class="mb-3">
                        <div class="fs-8 fw-bold text-muted mb-2 text-uppercase d-flex align-items-center gap-1" style="letter-spacing:.5px;">
                            <i class="bi bi-exclamation-octagon-fill text-danger"></i> Pesan Error
                        </div>
                        <div class="rounded-3 p-3" style="background:#fff5f5;border:1px solid #fecaca;">
                            <code class="text-danger" style="font-size:0.8rem;white-space:pre-wrap;word-break:break-word;">${esc(err.message)}</code>
                        </div>
                    </div>

                    <!-- File Sumber -->
                    <div class="mb-3">
                        <div class="fs-8 fw-bold text-muted mb-2 text-uppercase d-flex align-items-center gap-1" style="letter-spacing:.5px;">
                            <i class="bi bi-file-code-fill text-primary"></i> File Sumber
                        </div>
                        <div class="rounded-3 p-3 d-flex align-items-center gap-3 flex-wrap" style="background:#f0f9ff;border:1px solid #bae6fd;">
                            <code class="text-primary flex-grow-1" style="font-size:0.78rem;word-break:break-all;">${esc(err.file)}</code>
                            <span class="badge fw-bold flex-shrink-0" style="background:#fefce8;color:#854d0e;border:1px solid #fde68a;font-size:0.78rem;">
                                Baris ${esc(err.line || '?')}
                            </span>
                        </div>
                    </div>

                    <!-- Stack Trace -->
                    <div>
                        <div class="fs-8 fw-bold text-muted mb-2 text-uppercase d-flex align-items-center gap-1" style="letter-spacing:.5px;">
                            <i class="bi bi-list-ol text-secondary"></i> Stack Trace
                        </div>
                        ${traceHtml}
                    </div>

                    ${contextHtml}
                `;

                this._traceModal.show();
            },

            // ─── Clear All ─────────────────────────────────────────────
            async confirmClearAll() {
                const result = await Swal.fire({
                    title:             '<span class="text-danger fw-bold">Hapus Semua Log Error?</span>',
                    html:              `<p class="text-muted fs-7">Tindakan ini akan menghapus <strong class="text-danger">${this.totalErrors}</strong> entri error secara permanen dan tidak dapat dibatalkan.</p>`,
                    icon:              'warning',
                    showCancelButton:  true,
                    confirmButtonColor:'#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: '<i class="bi bi-trash3-fill me-1"></i> Ya, Hapus Semua',
                    cancelButtonText:  'Batal',
                });
                if (!result.isConfirmed) return;

                this.loadingClear = true;
                try {
                    await axios.post('/SINTA-SaaS/api/v1/error-monitor/clear');
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil Dihapus',
                        text: 'Semua log error telah dibersihkan.',
                        timer: 2000,
                        showConfirmButton: false,
                        toast: true,
                        position: 'top-end'
                    });
                    this.loadErrors(1);
                } catch {
                    Swal.fire({ icon: 'error', title: 'Gagal', text: 'Tidak dapat menghapus log error.', confirmButtonColor: '#2563eb' });
                } finally {
                    this.loadingClear = false;
                }
            },

            // ─── Delete One ────────────────────────────────────────────
            async deleteOne(id) {
                try {
                    await axios.post('/SINTA-SaaS/api/v1/error-monitor/delete', { id });
                    Swal.fire({
                        icon: 'success',
                        title: 'Log Dihapus',
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 1800,
                        timerProgressBar: true
                    });
                    this.loadErrors(this.currentPage);
                } catch {
                    Swal.fire({ icon: 'error', title: 'Gagal', text: 'Tidak dapat menghapus log ini.', confirmButtonColor: '#2563eb' });
                }
            },

            // ─── UI Helpers ────────────────────────────────────────────
            formatDateTime(raw) {
                if (!raw) return '-';
                const d = new Date(raw.replace(/-/g, '/'));
                if (isNaN(d)) return raw;
                return d.toLocaleString('id-ID', {
                    day: '2-digit', month: 'short', year: 'numeric',
                    hour: '2-digit', minute: '2-digit', second: '2-digit'
                });
            },

            shortenPath(path) {
                if (!path) return '-';
                const parts = (path || '').replace(/\\/g, '/').split('/');
                return '…/' + parts.slice(-3).join('/');
            },

            shortenUrl(url) {
                if (!url) return '-';
                try { return new URL(url).pathname; } catch { return (url + '').substring(0, 40); }
            },

            levelBadgeClass(level) {
                const l = (level || '').toLowerCase();
                if (l.includes('fatal') || l.includes('exception') || l === 'e_error') return 'bg-danger text-white';
                if (l.includes('warning') || l === 'e_warning') return 'bg-warning text-dark';
                if (l.includes('notice')  || l === 'e_notice')  return 'bg-info text-white';
                if (l.includes('deprecat'))                       return 'bg-secondary text-white';
                return 'bg-dark text-white';
            },

            levelCardClass(level) {
                const l = (level || '').toLowerCase();
                if (l.includes('fatal') || l.includes('exception') || l === 'e_error') return 'border-start border-danger border-3';
                if (l.includes('warning')) return 'border-start border-warning border-3';
                if (l.includes('notice'))  return 'border-start border-info border-3';
                return 'border-start border-secondary border-3';
            },

            levelIconBg(level) {
                const l = (level || '').toLowerCase();
                if (l.includes('fatal') || l.includes('exception') || l === 'e_error') return 'bg-danger-subtle text-danger';
                if (l.includes('warning')) return 'bg-warning-subtle text-warning';
                if (l.includes('notice'))  return 'bg-info-subtle text-info';
                return 'bg-secondary-subtle text-secondary';
            },

            levelIcon(level) {
                const l = (level || '').toLowerCase();
                if (l.includes('fatal') || l.includes('exception') || l === 'e_error') return 'bi bi-x-octagon-fill';
                if (l.includes('warning')) return 'bi bi-exclamation-triangle-fill';
                if (l.includes('notice'))  return 'bi bi-info-circle-fill';
                return 'bi bi-bug-fill';
            },
        }
    });
}
</script>
