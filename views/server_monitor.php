<?php
/**
 * View: Server & Tenant Resource Monitor Dashboard
 * Akses: Super Admin Only
 * Stack: Vue 3 CDN + Tailwind CSS (preflight OFF)
 */

// Suppress Tailwind CDN warning + disable preflight agar tidak clash dengan Bootstrap
?>

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
<script src="/dapodik-spmb/assets/js/tailwindcss.js"></script>

<style>
/* Progress bar gradient animation */
@keyframes barGrow { from { width: 0 } }
.progress-bar-anim { animation: barGrow 0.8s ease-out; }

/* Pulse dot */
@keyframes pulse-dot { 0%,100% { opacity:1 } 50% { opacity:0.4 } }
.pulse-dot { animation: pulse-dot 1.5s ease-in-out infinite; }

/* Tabel hover */
.tenant-row:hover { background: #f0f9ff !important; }

/* fs-9 compat */
.fs-9 { font-size: 0.72rem !important; }
</style>

<!-- ================================================================
     ROOT VUE APP
     ================================================================ -->
<div id="serverMonitorApp" v-cloak>

    <!-- PAGE HEADER -->
    <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 pt-2 pb-3 mb-4 border-bottom">
        <div>
            <h2 class="fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                <span class="d-inline-flex align-items-center justify-content-center rounded-3 shadow-sm flex-shrink-0"
                      style="width:40px;height:40px;background:linear-gradient(135deg,#dbeafe,#bfdbfe);">
                    <i class="bi bi-hdd-network-fill text-primary fs-5"></i>
                </span>
                Server & Resource Monitor
            </h2>
            <p class="text-muted fs-7 mb-0">
                Pemantauan kesehatan server secara global dan penggunaan resource oleh masing-masing sekolah.
                <span class="badge bg-primary-subtle border border-primary border-opacity-25 ms-1 fs-9" style="color: #084298;">
                    Super Admin Only
                </span>
            </p>
        </div>
        <div class="d-flex align-items-center gap-3">
            <!-- Live Indicator -->
            <div class="d-flex align-items-center gap-2 rounded-3 px-3 py-2 border"
                 style="background:#f8fafc;font-size:0.78rem;">
                <span class="rounded-circle pulse-dot flex-shrink-0"
                      :style="{ width:'8px', height:'8px', background: loading ? '#f59e0b' : '#22c55e' }"></span>
                <span class="text-muted fw-semibold" v-if="loading">Memperbarui...</span>
                <span class="text-muted fw-semibold" v-else>Live · Refresh {{ countdown }}s</span>
            </div>
            <!-- Manual Refresh -->
            <button class="btn btn-light border rounded-3 px-3 fs-8 d-flex align-items-center gap-2"
                    @click="fetchData" :disabled="loading" style="height:38px;">
                <i class="bi bi-arrow-clockwise" :style="loading ? 'animation:spin 1s linear infinite;display:inline-block;' : ''"></i>
                Refresh
            </button>
        </div>
    </div>

    <!-- NAVIGATION TABS -->
    <div class="border-bottom mb-4">
        <ul class="nav nav-tabs border-0 flex gap-4">
            <li class="nav-item">
                <button type="button" class="px-3 py-2 fw-semibold border-0 bg-transparent position-relative text-decoration-none"
                   :class="activeTab === 'resources' ? 'text-primary border-bottom border-primary border-2' : 'text-muted'"
                   @click="activeTab = 'resources'"
                   style="cursor:pointer; font-size: 0.9rem; transition: all 0.2s;">
                    <i class="bi bi-cpu-fill me-1"></i> Resource Monitor
                </button>
            </li>
            <li class="nav-item">
                <button type="button" class="px-3 py-2 fw-semibold border-0 bg-transparent position-relative text-decoration-none"
                   :class="activeTab === 'network' ? 'text-primary border-bottom border-primary border-2' : 'text-muted'"
                   @click="activeTab = 'network'"
                   style="cursor:pointer; font-size: 0.9rem; transition: all 0.2s;">
                    <i class="bi bi-hdd-network-fill me-1"></i> Network Interfaces
                </button>
            </li>
        </ul>
    </div>

    <div v-show="activeTab === 'resources'">
        <!-- ============================================================
             BAGIAN 1: GLOBAL SERVER METRICS
             ============================================================ -->
        <div class="row g-3 mb-4">

        <!-- CPU -->
        <div class="col-lg-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-4 p-4 h-100" style="background:#fff;">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div>
                        <div class="fs-9 text-muted fw-semibold text-uppercase" style="letter-spacing:.6px;">CPU Load Avg</div>
                        <div class="fw-bold mt-1" style="font-size:1.8rem;line-height:1;"
                             :style="{ color: usageColor(metrics.cpu?.usage_percent) }">
                            <span v-if="metrics.cpu?.available">{{ metrics.cpu.usage_percent }}%</span>
                            <span v-else class="fs-6 text-muted fw-semibold">N/A</span>
                        </div>
                    </div>
                    <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0"
                         style="width:48px;height:48px;"
                         :style="{ background: usageBgColor(metrics.cpu?.usage_percent) }">
                        <i class="bi bi-cpu-fill fs-4" :style="{ color: usageColor(metrics.cpu?.usage_percent) }"></i>
                    </div>
                </div>
                <div class="progress rounded-pill mb-2" style="height:6px;background:#f1f5f9;">
                    <div class="progress-bar rounded-pill progress-bar-anim"
                         role="progressbar"
                         aria-label="CPU Load Average"
                         :aria-valuenow="metrics.cpu?.usage_percent || 0"
                         aria-valuemin="0"
                         aria-valuemax="100"
                         :style="{ width: (metrics.cpu?.usage_percent || 0) + '%', background: usageColor(metrics.cpu?.usage_percent) }">
                    </div>
                </div>
                <div class="d-flex justify-content-between" style="font-size:0.7rem;color:#94a3b8;">
                    <span>Load: {{ metrics.cpu?.load_1 }} / {{ metrics.cpu?.load_5 }} / {{ metrics.cpu?.load_15 }}</span>
                    <span>{{ metrics.cpu?.cpu_count }} Core</span>
                </div>
            </div>
        </div>

        <!-- RAM -->
        <div class="col-lg-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-4 p-4 h-100" style="background:#fff;">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div>
                        <div class="fs-9 text-muted fw-semibold text-uppercase" style="letter-spacing:.6px;">Memory (RAM)</div>
                        <div class="fw-bold mt-1" style="font-size:1.8rem;line-height:1;"
                             :style="{ color: usageColor(metrics.ram?.usage_percent) }">
                            <span v-if="metrics.ram?.available">{{ metrics.ram.usage_percent }}%</span>
                            <span v-else class="fs-6 text-muted fw-semibold">N/A</span>
                        </div>
                    </div>
                    <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0"
                         style="width:48px;height:48px;"
                         :style="{ background: usageBgColor(metrics.ram?.usage_percent) }">
                        <i class="bi bi-memory fs-4" :style="{ color: usageColor(metrics.ram?.usage_percent) }"></i>
                    </div>
                </div>
                <div class="progress rounded-pill mb-2" style="height:6px;background:#f1f5f9;">
                    <div class="progress-bar rounded-pill progress-bar-anim"
                         role="progressbar"
                         aria-label="Memory RAM Usage"
                         :aria-valuenow="metrics.ram?.usage_percent || 0"
                         aria-valuemin="0"
                         aria-valuemax="100"
                         :style="{ width: (metrics.ram?.usage_percent || 0) + '%', background: usageColor(metrics.ram?.usage_percent) }">
                    </div>
                </div>
                <div class="d-flex justify-content-between" style="font-size:0.7rem;color:#94a3b8;">
                    <span>Dipakai: {{ metrics.ram?.used_gb }} GB</span>
                    <span>Total: {{ metrics.ram?.total_gb }} GB</span>
                </div>
            </div>
        </div>

        <!-- Disk -->
        <div class="col-lg-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-4 p-4 h-100" style="background:#fff;">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div>
                        <div class="fs-9 text-muted fw-semibold text-uppercase" style="letter-spacing:.6px;">Main Disk</div>
                        <div class="fw-bold mt-1" style="font-size:1.8rem;line-height:1;"
                             :style="{ color: usageColor(metrics.disk?.usage_percent) }">
                            <span v-if="metrics.disk?.available">{{ metrics.disk.usage_percent }}%</span>
                            <span v-else class="fs-6 text-muted fw-semibold">N/A</span>
                        </div>
                    </div>
                    <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0"
                         style="width:48px;height:48px;"
                         :style="{ background: usageBgColor(metrics.disk?.usage_percent) }">
                        <i class="bi bi-hdd-fill fs-4" :style="{ color: usageColor(metrics.disk?.usage_percent) }"></i>
                    </div>
                </div>
                <div class="progress rounded-pill mb-2" style="height:6px;background:#f1f5f9;">
                    <div class="progress-bar rounded-pill progress-bar-anim"
                         role="progressbar"
                         aria-label="Disk Space Usage"
                         :aria-valuenow="metrics.disk?.usage_percent || 0"
                         aria-valuemin="0"
                         aria-valuemax="100"
                         :style="{ width: (metrics.disk?.usage_percent || 0) + '%', background: usageColor(metrics.disk?.usage_percent) }">
                    </div>
                </div>
                <div class="d-flex justify-content-between" style="font-size:0.7rem;color:#94a3b8;">
                    <span>Dipakai: {{ metrics.disk?.used_gb }} GB</span>
                    <span>Total: {{ metrics.disk?.total_gb }} GB</span>
                </div>
            </div>
        </div>

        <!-- Uptime / Info -->
        <div class="col-lg-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-4 p-4 h-100" style="background:#fff;">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div>
                        <div class="fs-9 text-muted fw-semibold text-uppercase" style="letter-spacing:.6px;">Server Uptime</div>
                        <div class="fw-bold mt-1 text-dark" style="font-size:1.2rem;line-height:1.3;">
                            {{ metrics.uptime?.human || 'N/A' }}
                        </div>
                    </div>
                    <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0"
                         style="width:48px;height:48px;background:#f0fdf4;">
                        <i class="bi bi-clock-history fs-4 text-success"></i>
                    </div>
                </div>
                <div class="mt-3 pt-2 border-top">
                    <div class="d-flex justify-content-between align-items-center" style="font-size:0.72rem;color:#64748b;">
                        <span>Platform</span>
                        <span class="badge bg-light text-dark border font-monospace">{{ metrics.os || 'Unknown' }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-1" style="font-size:0.72rem;color:#64748b;">
                        <span>Terakhir Update</span>
                        <span class="font-monospace">{{ lastUpdated }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-1" style="font-size:0.72rem;color:#64748b;">
                        <span>Jumlah Sekolah</span>
                        <span class="fw-bold text-primary">{{ tenants.length }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================
         BAGIAN 2: TENANT RESOURCE TABLE
         ============================================================ -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-5">

        <!-- Table Toolbar -->
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 px-4 py-3"
             style="background:#f8fafc;border-bottom:1px solid #e2e8f0;">
            <div class="fw-semibold text-dark fs-7 d-flex align-items-center gap-2">
                <i class="bi bi-building-fill text-primary"></i>
                Penggunaan Resource Per Sekolah
                <span class="badge bg-primary-subtle border border-primary border-opacity-25 ms-1" style="color: #084298;">
                    {{ filteredTenants.length }} sekolah
                </span>
            </div>

            <div class="d-flex flex-wrap gap-2 align-items-center">
                <!-- Search -->
                <div class="position-relative">
                    <i class="bi bi-search position-absolute text-muted"
                       style="left:10px;top:50%;transform:translateY(-50%);font-size:.75rem;pointer-events:none;"></i>
                    <input type="text"
                           class="form-control rounded-pill border-0 bg-white ps-4 fs-8"
                           style="height:34px;min-width:200px;box-shadow:0 1px 4px rgba(0,0,0,.06);"
                           v-model="searchQuery"
                           placeholder="Cari nama sekolah...">
                </div>

                <!-- Sort By -->
                <select class="form-select rounded-pill border-0 bg-white fs-8"
                        style="height:34px;min-width:180px;box-shadow:0 1px 4px rgba(0,0,0,.06);"
                        v-model="sortBy" @change="sortDir = 'desc'">
                    <option value="">Urutkan Default</option>
                    <option value="active_sessions">Sesi Aktif ↓</option>
                    <option value="disk_mb">Penyimpanan ↓</option>
                    <option value="total_siswa">Total Siswa ↓</option>
                    <option value="quota_percent">Kuota % ↓</option>
                </select>
            </div>
        </div>

        <!-- Table -->
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr style="background:#f8fafc;border-bottom:2px solid #e2e8f0;font-size:0.75rem;color:#64748b;">
                        <th class="ps-4 py-3 fw-semibold">#</th>
                        <th class="py-3 fw-semibold" style="min-width:200px;">Nama Sekolah</th>
                        <th class="py-3 fw-semibold text-center" @click="toggleSort('active_sessions')" style="cursor:pointer;">
                            <span>Sesi Aktif</span>
                            <i class="bi ms-1" :class="sortIcon('active_sessions')"></i>
                        </th>
                        <th class="py-3 fw-semibold text-center" @click="toggleSort('disk_mb')" style="cursor:pointer;">
                            <span>Penyimpanan</span>
                            <i class="bi ms-1" :class="sortIcon('disk_mb')"></i>
                        </th>
                        <th class="py-3 fw-semibold text-center" @click="toggleSort('total_users')" style="cursor:pointer;">
                            <span>Total Pengguna</span>
                            <i class="bi ms-1" :class="sortIcon('total_users')"></i>
                        </th>
                        <th class="py-3 fw-semibold">Kuota Storage</th>
                        <th class="py-3 fw-semibold text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Loading skeleton -->
                    <tr v-if="loading && tenants.length === 0">
                        <td colspan="7" class="text-center py-5">
                            <div class="spinner-border text-primary" style="width:2rem;height:2rem;" role="status"></div>
                            <div class="text-muted fs-8 mt-2">Memuat data resource sekolah...</div>
                        </td>
                    </tr>

                    <!-- Empty -->
                    <tr v-else-if="filteredTenants.length === 0">
                        <td colspan="7" class="text-center py-5 text-muted fs-8">
                            <i class="bi bi-building fs-2 d-block mb-2"></i>
                            Tidak ada sekolah ditemukan.
                        </td>
                    </tr>

                    <!-- Data rows -->
                    <tr v-else
                        v-for="(t, idx) in filteredTenants"
                        :key="t.id"
                        class="tenant-row"
                        style="font-size:0.82rem;border-bottom:1px solid #f1f5f9;">

                        <!-- No -->
                        <td class="ps-4 text-muted fs-9 fw-semibold">{{ idx + 1 }}</td>

                        <!-- Nama Sekolah -->
                        <td>
                            <div class="fw-semibold text-dark">{{ t.nama_sekolah }}</div>
                            <div class="d-flex gap-1 mt-1 flex-wrap">
                                <span class="badge bg-light text-dark border fs-9">{{ t.npsn }}</span>
                                <span class="badge fs-9"
                                      :class="t.status === 'active' ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning'">
                                    {{ t.status }}
                                </span>
                            </div>
                        </td>

                        <!-- Sesi Aktif -->
                        <td class="text-center">
                            <div class="d-inline-flex align-items-center gap-2">
                                <span class="rounded-circle pulse-dot"
                                      v-if="t.active_sessions > 0"
                                      style="width:7px;height:7px;background:#22c55e;display:inline-block;"></span>
                                <span class="fw-bold fs-6"
                                      :class="t.active_sessions > 0 ? 'text-success' : 'text-muted'">
                                    {{ t.active_sessions }}
                                </span>
                            </div>
                            <div class="text-muted fs-9 mt-1">user online</div>
                        </td>

                        <!-- Penyimpanan -->
                        <td class="text-center">
                            <div class="fw-bold"
                                 :class="t.disk_mb > 100 ? 'text-warning' : 'text-dark'">
                                {{ t.disk_mb >= 1024
                                    ? (t.disk_mb / 1024).toFixed(2) + ' GB'
                                    : t.disk_mb.toFixed(1) + ' MB'
                                }}
                            </div>
                            <div class="text-muted fs-9 mt-1">dari {{ t.quota_mb >= 1024 ? (t.quota_mb/1024) + ' GB' : t.quota_mb + ' MB' }}</div>
                        </td>

                        <!-- Total Pengguna -->
                        <td class="text-center">
                            <div class="fw-bold text-dark">{{ t.total_users.toLocaleString('id-ID') }}</div>
                            <div class="text-muted fs-9 mt-1">{{ t.total_siswa }} siswa, {{ t.total_staff }} staff</div>
                        </td>

                        <!-- Kuota Progress -->
                        <td style="min-width:160px;">
                            <div class="d-flex align-items-center justify-content-between mb-1" style="font-size:0.7rem;color:#64748b;">
                                <span>{{ t.quota_percent }}%</span>
                                <span>{{ t.paket_aktif }}</span>
                            </div>
                            <div class="progress rounded-pill" style="height:6px;background:#f1f5f9;">
                                <div class="progress-bar rounded-pill progress-bar-anim"
                                     role="progressbar"
                                     :aria-label="'Penggunaan Kuota Penyimpanan ' + t.nama_sekolah"
                                     :aria-valuenow="t.quota_percent"
                                     aria-valuemin="0"
                                     aria-valuemax="100"
                                     :style="{ width: t.quota_percent + '%', background: usageColor(t.quota_percent) }">
                                </div>
                            </div>
                        </td>

                        <!-- Status Quota -->
                        <td class="text-center pe-3">
                            <span class="badge rounded-pill fw-semibold px-3 py-1 fs-9"
                                  :class="{
                                      'bg-danger text-white':    t.quota_status === 'Kritis',
                                      'bg-warning text-dark':    t.quota_status === 'Peringatan',
                                      'bg-success-subtle text-success border border-success border-opacity-25': t.quota_status === 'Normal',
                                  }">
                                <i class="bi me-1"
                                   :class="{
                                       'bi-exclamation-octagon-fill': t.quota_status === 'Kritis',
                                       'bi-exclamation-triangle-fill': t.quota_status === 'Peringatan',
                                       'bi-check-circle-fill': t.quota_status === 'Normal',
                                   }"></i>
                                {{ t.quota_status }}
                            </span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Table Footer Summary -->
        <div class="px-4 py-3 border-top d-flex flex-wrap align-items-center justify-content-between gap-2"
             style="background:#f8fafc;font-size:0.75rem;color:#64748b;"
             v-if="tenants.length > 0">
            <div class="d-flex gap-4 flex-wrap">
                <span>Total Sesi Online: <strong class="text-success">{{ totalActiveSessions }}</strong></span>
                <span>Total Penyimpanan Terpakai: <strong class="text-primary">{{ totalDiskFormatted }}</strong></span>
                <span>Total Siswa Terdaftar: <strong class="text-dark">{{ totalSiswa.toLocaleString('id-ID') }}</strong></span>
            </div>
            <div class="text-muted fs-9">Polling setiap 5 detik · Data aktif 15 menit terakhir</div>
    </div><!-- /v-show="activeTab === 'resources'" -->

    <!-- ============================================================
         BAGIAN 3: NETWORK INTERFACES MONITOR & CONFIG
         ============================================================ -->
    <div v-show="activeTab === 'network'">
        <div class="row g-3 mb-5">
            <!-- Cards for each interface -->
            <div class="col-md-6" v-for="net in networkInterfaces" :key="net.interface">
                <div class="card border-0 shadow-sm rounded-4 p-4 h-100" style="background:#fff; transition: transform 0.2s; cursor: default;">
                    <div class="d-flex align-items-start justify-content-between mb-3 gap-2">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0"
                                 style="width:48px;height:48px;background:#eff6ff;">
                                <i class="bi bi-hdd-network-fill text-primary fs-4"></i>
                            </div>
                            <div>
                                <h4 class="fw-bold text-dark mb-0 fs-5">{{ net.interface }}</h4>
                                <span class="badge rounded-pill fw-semibold px-2 py-0.5 fs-9 mt-1"
                                      :class="net.dhcp ? 'bg-success-subtle text-success border border-success border-opacity-25' : 'bg-warning-subtle text-warning border border-warning border-opacity-25'">
                                    {{ net.dhcp ? 'DHCP Enabled' : 'Static IP' }}
                                </span>
                            </div>
                        </div>
                        <button type="button" class="btn btn-primary-subtle text-primary border border-primary border-opacity-10 btn-sm rounded-3 px-3 fs-8 fw-semibold"
                                @click="openConfigModal(net)">
                            <i class="bi bi-gear-fill me-1"></i> Ubah Konfigurasi
                        </button>
                    </div>

                    <div class="row g-2 pt-2 border-top">
                        <div class="col-6">
                            <div class="fs-9 text-muted fw-semibold text-uppercase" style="letter-spacing:.5px;">MAC Address</div>
                            <div class="text-dark font-monospace fs-8 mt-0.5">{{ net.mac || 'N/A' }}</div>
                        </div>
                        <div class="col-6">
                            <div class="fs-9 text-muted fw-semibold text-uppercase" style="letter-spacing:.5px;">IPv4 Address / CIDR</div>
                            <div class="text-dark font-monospace fs-8 mt-0.5">
                                {{ net.ipv4 ? net.ipv4 + (net.cidr ? '/' + net.cidr : '') : 'N/A' }}
                            </div>
                        </div>
                        <div class="col-6 mt-3">
                            <div class="fs-9 text-muted fw-semibold text-uppercase" style="letter-spacing:.5px;">Default Gateway</div>
                            <div class="text-dark font-monospace fs-8 mt-0.5">{{ net.gateway || 'N/A' }}</div>
                        </div>
                        <div class="col-6 mt-3">
                            <div class="fs-9 text-muted fw-semibold text-uppercase" style="letter-spacing:.5px;">DNS Servers</div>
                            <div class="text-dark font-monospace fs-8 mt-0.5">
                                {{ Array.isArray(net.dns) ? net.dns.join(', ') : (net.dns || 'N/A') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State for network interfaces -->
            <div class="col-12 text-center py-5 text-muted fs-8 card border-0 shadow-sm rounded-4" v-if="networkInterfaces.length === 0">
                <i class="bi bi-wifi-off fs-2 d-block mb-2"></i>
                Tidak ada adapter jaringan eksternal yang terdeteksi.
            </div>
        </div>
    </div>

    <!-- ============================================================
         MODAL FORM CONFIGURE NETWORK INTERFACE
         ============================================================ -->
    <div v-if="showNetworkModal" class="fixed inset-0 z-[1050] flex items-center justify-center overflow-x-hidden overflow-y-auto outline-none" style="background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px);">
        <div class="relative w-full max-w-lg mx-auto my-6 px-4">
            <!-- Modal Content -->
            <div class="relative flex flex-col w-full bg-white border border-slate-200 rounded-2xl shadow-2xl outline-none">
                
                <!-- Modal Header -->
                <div class="flex items-center justify-between p-4 border-b border-slate-100">
                    <h3 class="text-lg font-bold text-slate-800 d-flex align-items-center gap-2 mb-0">
                        <i class="bi bi-sliders text-primary"></i>
                        Konfigurasi Adapter: {{ form.interface }}
                    </h3>
                    <button type="button" class="text-slate-400 hover:text-slate-600 bg-transparent border-0 text-xl font-semibold cursor-pointer" @click="closeConfigModal">
                        &times;
                    </button>
                </div>

                <!-- Modal Body -->
                <form @submit.prevent="submitNetworkConfig">
                    <div class="p-4 space-y-3 text-start">
                        <!-- DHCP or Static Selector -->
                        <div class="mb-3">
                            <label for="net_mode_select" class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Tipe Konfigurasi IP</label>
                            <select id="net_mode_select" name="dhcp" v-model="form.dhcp" class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                                <option :value="true">DHCP (Otomatis)</option>
                                <option :value="false">Static (Manual)</option>
                            </select>
                        </div>

                        <!-- DHCP Info Helper -->
                        <div v-if="form.dhcp" class="p-3 bg-blue-50 text-blue-700 rounded-lg text-xs flex gap-2 mb-2">
                            <i class="bi bi-info-circle-fill"></i>
                            <div>
                                Server akan meminta IP Address, Default Gateway, dan DNS Server secara otomatis dari DHCP server lokal.
                            </div>
                        </div>

                        <!-- Static Configuration Inputs -->
                        <div v-else class="space-y-3 pt-2">
                            <!-- IP Address & Subnet -->
                            <div class="mb-3">
                                <label for="net_ipv4_input" class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">IPv4 Address & CIDR Prefix</label>
                                <input type="text" id="net_ipv4_input" name="ipv4" v-model="form.ipv4" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-800 font-monospace focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition" placeholder="contoh: 192.168.1.10/24" required autocomplete="off">
                                <span class="text-slate-400 d-block mt-1" style="font-size: 0.72rem;">Pastikan menyertakan prefix CIDR di belakang IP (e.g. /24 untuk netmask 255.255.255.0).</span>
                            </div>

                            <!-- Default Gateway -->
                            <div class="mb-3">
                                <label for="net_gateway_input" class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Default Gateway</label>
                                <input type="text" id="net_gateway_input" name="gateway" v-model="form.gateway" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-800 font-monospace focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition" placeholder="contoh: 192.168.1.1" autocomplete="off">
                            </div>

                            <!-- DNS Servers -->
                            <div class="mb-3">
                                <label for="net_dns_input" class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">DNS Servers (Pisahkan dengan koma)</label>
                                <input type="text" id="net_dns_input" name="dns" v-model="form.dns" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-800 font-monospace focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition" placeholder="contoh: 8.8.8.8, 8.8.4.4" autocomplete="off">
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="flex items-center justify-end p-4 border-t border-slate-100 gap-2">
                        <button type="button" class="btn btn-light border rounded-lg px-4 fs-8 font-semibold text-slate-600" @click="closeConfigModal" :disabled="formSubmitting">
                            Batal
                        </button>
                        <button type="submit" class="btn btn-primary rounded-lg px-4 fs-8 font-semibold d-flex align-items-center gap-2" :disabled="formSubmitting">
                            <span v-if="formSubmitting" class="spinner-border spinner-border-sm" role="status"></span>
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div><!-- /serverMonitorApp -->


<!-- ================================================================
     VUE 3 SCRIPT: Polling + Cleanup
     ================================================================ -->
<script>
{
    // CSS spin keyframe untuk icon refresh
    const _s = document.createElement('style');
    _s.textContent = `
        [v-cloak] { display:none!important; }
        @keyframes spin { to { transform:rotate(360deg); } }
    `;
    document.head.appendChild(_s);

    const POLL_INTERVAL_MS = 5000; // 5 detik
    const API_URL = '/dapodik-spmb/api/v1/super-admin/server-monitor/fetch';

    window.VueAppRegistry.register('#serverMonitorApp', {
        data() {
            return {
                metrics:     { cpu: null, ram: null, disk: null, uptime: null, os: '' },
                tenants:     [],
                networkInterfaces: [],
                loading:     false,
                lastUpdated: '--:--:--',
                searchQuery: '',
                sortBy:      '',
                sortDir:     'desc',
                countdown:   5,
                _pollTimer:   null,
                _countTimer:  null,
                activeTab:   'resources',
                showNetworkModal: false,
                formSubmitting: false,
                form: {
                    interface: '',
                    dhcp: true,
                    ipv4: '',
                    gateway: '',
                    dns: ''
                }
            };
        },

        computed: {
            filteredTenants() {
                let list = this.tenants;

                // Filter search
                if (this.searchQuery.trim()) {
                    const q = this.searchQuery.toLowerCase();
                    list = list.filter(t => t.nama_sekolah.toLowerCase().includes(q) || t.npsn.includes(q));
                }

                // Sort
                if (this.sortBy) {
                    list = [...list].sort((a, b) => {
                        const va = parseFloat(a[this.sortBy]) || 0;
                        const vb = parseFloat(b[this.sortBy]) || 0;
                        return this.sortDir === 'desc' ? vb - va : va - vb;
                    });
                }

                return list;
            },

            totalActiveSessions() {
                return this.tenants.reduce((s, t) => s + t.active_sessions, 0);
            },
            totalDiskFormatted() {
                const mb = this.tenants.reduce((s, t) => s + parseFloat(t.disk_mb || 0), 0);
                if (mb >= 1024) {
                    return (mb / 1024).toFixed(2) + ' GB';
                }
                return mb.toFixed(2) + ' MB';
            },
            totalSiswa() {
                return this.tenants.reduce((s, t) => s + t.total_siswa, 0);
            },
        },

        mounted() {
            this.fetchData();
            this._startPolling();
        },

        unmounted() {
            // ✅ CRITICAL: Hentikan polling saat komponen di-unmount (Turbo Drive / pergantian halaman)
            this._stopPolling();
        },

        methods: {
            // ─── Polling Lifecycle ────────────────────────────────
            _startPolling() {
                this._stopPolling(); // Pastikan tidak ada timer ganda

                this._pollTimer = setInterval(() => {
                    this.fetchData();
                    this.countdown = 5;
                }, POLL_INTERVAL_MS);

                // Countdown timer (setiap detik)
                this._countTimer = setInterval(() => {
                    if (this.countdown > 1) {
                        this.countdown--;
                    }
                }, 1000);
            },

            _stopPolling() {
                if (this._pollTimer)  { clearInterval(this._pollTimer);  this._pollTimer  = null; }
                if (this._countTimer) { clearInterval(this._countTimer); this._countTimer = null; }
            },

            // ─── Data Fetching ────────────────────────────────────
            async fetchData() {
                if (this.loading) return;
                this.loading = true;
                try {
                    const res  = await axios.get(API_URL);
                    const data = res.data;

                    if (data.success) {
                        this.metrics           = data.global_metrics || {};
                        this.tenants           = data.tenants        || [];
                        this.networkInterfaces = data.network_interfaces || [];
                        this.lastUpdated = data.timestamp
                            ? new Date(data.timestamp.replace(/-/g,'/')).toLocaleTimeString('id-ID')
                            : '--:--:--';
                        this.countdown = 5;
                    }
                } catch (err) {
                    console.error('[ServerMonitor] Fetch error:', err?.message);
                    // Tidak menampilkan Swal agar tidak spam setiap 5 detik
                } finally {
                    this.loading = false;
                }
            },

            // ─── Sorting ──────────────────────────────────────────
            toggleSort(col) {
                if (this.sortBy === col) {
                    this.sortDir = this.sortDir === 'desc' ? 'asc' : 'desc';
                } else {
                    this.sortBy  = col;
                    this.sortDir = 'desc';
                }
            },

            sortIcon(col) {
                if (this.sortBy !== col) return 'bi-chevron-expand text-muted';
                return this.sortDir === 'desc' ? 'bi-sort-down text-primary' : 'bi-sort-up text-primary';
            },

            // ─── UI Helpers ───────────────────────────────────────
            usageColor(pct) {
                const p = parseFloat(pct) || 0;
                if (p >= 80) return '#ef4444'; // Merah
                if (p >= 60) return '#f59e0b'; // Kuning
                return '#22c55e';              // Hijau
            },

            usageBgColor(pct) {
                const p = parseFloat(pct) || 0;
                if (p >= 80) return '#fef2f2';
                if (p >= 60) return '#fffbeb';
                return '#f0fdf4';
            },

            // ─── Network Config Modals ────────────────────────────
            openConfigModal(net) {
                this.form.interface = net.interface;
                this.form.dhcp = net.dhcp;
                this.form.ipv4 = net.ipv4 ? `${net.ipv4}${net.cidr ? '/' + net.cidr : ''}` : '';
                this.form.gateway = net.gateway || '';
                this.form.dns = Array.isArray(net.dns) ? net.dns.join(', ') : (net.dns || '');
                this.showNetworkModal = true;
            },

            closeConfigModal() {
                this.showNetworkModal = false;
                this.form.interface = '';
                this.form.dhcp = true;
                this.form.ipv4 = '';
                this.form.gateway = '';
                this.form.dns = '';
            },

            async submitNetworkConfig() {
                this.formSubmitting = true;
                try {
                    const res = await axios.post('/dapodik-spmb/api/v1/super-admin/server-monitor/save-network', this.form);
                    if (res.data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil',
                            text: res.data.message || 'Konfigurasi jaringan berhasil diperbarui.',
                            confirmButtonText: 'OK'
                        });
                        this.closeConfigModal();
                        this.fetchData();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal',
                            text: res.data.error || 'Terjadi kesalahan saat memperbarui konfigurasi.',
                            confirmButtonText: 'OK'
                        });
                    }
                } catch (err) {
                    console.error('[ServerMonitor] Save error:', err);
                    const errMsg = err.response?.data?.error || err.message || 'Terjadi kesalahan koneksi server.';
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal Menyimpan',
                        text: errMsg,
                        confirmButtonText: 'OK'
                    });
                } finally {
                    this.formSubmitting = false;
                }
            }
        }
    });
}
</script>
