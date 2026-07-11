<!-- ═══════════════════════════════════════════════════════════
         TAB 1: DASHBOARD MONITORING
    ════════════════════════════════════════════════════════════ -->
    <div v-show="activeTab === 'dashboard'">
        <!-- Loading State -->
        <div v-if="loadingDashboard" class="text-center py-5">
            <div class="spinner-border text-primary"></div>
            <p class="text-muted mt-2 fs-7">Memuat data monitoring...</p>
        </div>

        <div v-else>
            <!-- KPI Row -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3">
                    <div class="kpi-card">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <p class="text-muted fs-8 fw-semibold text-uppercase mb-1">Siswa Aktif</p>
                                <div class="kpi-value text-dark">{{ kpi.total_siswa_aktif }}</div>
                            </div>
                            <div class="kpi-icon" style="background:#eff6ff;color:#2563eb;">
                                <i class="bi bi-people-fill"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="kpi-card">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <p class="text-muted fs-8 fw-semibold text-uppercase mb-1">Kasus Bulan Ini</p>
                                <div class="kpi-value" style="color:var(--bk-amber);">{{ kpi.kasus_bulan_ini }}</div>
                            </div>
                            <div class="kpi-icon" style="background:#fff7ed;color:#f59e0b;">
                                <i class="bi bi-exclamation-triangle-fill"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="kpi-card">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <p class="text-muted fs-8 fw-semibold text-uppercase mb-1">Kasus Terbuka</p>
                                <div class="kpi-value" style="color:var(--bk-red);">{{ kpi.kasus_terbuka }}</div>
                            </div>
                            <div class="kpi-icon" style="background:#fef2f2;color:#ef4444;">
                                <i class="bi bi-folder2-open"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="kpi-card">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <p class="text-muted fs-8 fw-semibold text-uppercase mb-1">Total Alumni</p>
                                <div class="kpi-value" style="color:var(--bk-green);">{{ kpi.total_alumni }}</div>
                            </div>
                            <div class="kpi-icon" style="background:#ecfdf5;color:#10b981;">
                                <i class="bi bi-mortarboard-fill"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Distribusi Kasus -->
            <div class="bk-card p-4">
                <h6 class="fw-bold mb-3"><i class="bi bi-pie-chart-fill me-2" style="color:var(--bk-primary);"></i>Distribusi Kasus per Jenis</h6>
                <div v-if="kpi.distribusi_kasus && kpi.distribusi_kasus.length > 0">
                    <div class="row g-2">
                        <div v-for="(item, idx) in kpi.distribusi_kasus" :key="idx" class="col-md-4">
                            <div class="d-flex align-items-center gap-2 p-2 rounded-3" style="background:var(--bk-bg);">
                                <span class="pie-legend-dot" :style="'background:' + pieColors[idx % pieColors.length]"></span>
                                <span class="fw-semibold fs-7">{{ item.jenis_kasus }}</span>
                                <span class="ms-auto badge bg-secondary rounded-pill">{{ item.total }}</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div v-else class="text-center py-4 text-muted">
                    <i class="bi bi-pie-chart fs-1 d-block mb-2"></i>
                    Belum ada data kasus yang tercatat.
                </div>
            </div>
        </div>
    </div>
