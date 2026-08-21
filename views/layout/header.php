<?php
/**
 * Layout Component: Header
 * Berisi navbar utama, burger button, nama sekolah (tenant), search bar, dan user dropdown.
 */
use App\Config\Database;

// Dapatkan nama sekolah/tenant aktif secara dinamis dari database untuk indikator tenant
$tenantId = $_SESSION['tenant_id'] ?? null;
$namaSekolah = 'Pusat Kendali SaaS (Global)';
$npsnSekolah = 'PLATFORM';

if ($tenantId) {
    try {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT nama_sekolah, npsn FROM core.tenants WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $tenantId]);
        $row = $stmt->fetch();
        if ($row) {
            $namaSekolah = $row['nama_sekolah'];
            $npsnSekolah = $row['npsn'];
        }
    } catch (\Throwable $e) {
        $namaSekolah = 'Sekolah Terisolasi';
    }
}
?>
<header id="app-header" class="navbar navbar-expand navbar-custom sticky-top px-3 px-md-4 shadow-sm" style="background: rgba(255,255,255,0.95); backdrop-filter: blur(10px); border-bottom: 1px solid rgba(0,0,0,0.05); min-height: 70px;" data-turbo-permanent>
    <!-- Left Section -->
    <div class="d-flex align-items-center flex-grow-1 gap-2 gap-md-3 overflow-hidden">
        <!-- Burger Button Toggle -->
        <button class="btn btn-light text-secondary border-0 p-2 rounded-circle hover-lift transition me-1" id="sidebarToggle" type="button" aria-label="Toggle Sidebar">
            <i class="bi bi-list fs-4"></i>
        </button>

        <!-- Brand Logo / Name -->
        <a class="navbar-brand d-flex align-items-center text-decoration-none me-2 me-md-4 transition hover-lift" href="<?= $this->getBaseUrl() ?>/dashboard">
            <div class="bg-primary text-white rounded d-flex align-items-center justify-content-center me-2 shadow-sm" style="width: 36px; height: 36px;">
                <i class="bi bi-mortarboard-fill fs-5"></i>
            </div>
            <span class="fw-extrabold fs-5 text-dark tracking-tight d-none d-sm-inline">SINTA <span class="text-primary">SAAS</span></span>
        </a>

        <!-- Indikator Tenant (Nama Sekolah Aktif) -->
        <div class="tenant-indicator d-none d-md-flex align-items-center bg-light px-3 py-2 rounded-pill border-0 shadow-sm me-auto hover-bg-gray transition">
            <i class="bi bi-building-fill text-primary me-2 fs-7"></i>
            <div class="fs-8 fw-semibold text-dark text-truncate" style="max-width: 250px;">
                <?= htmlspecialchars($namaSekolah) ?> 
                <span class="text-muted fw-normal d-none d-lg-inline">(NPSN: <?= htmlspecialchars($npsnSekolah) ?>)</span>
            </div>
        </div>

        <!-- Global Search Bar -->
        <div class="search-bar-container d-none d-xl-block me-2 position-relative" style="width: 250px;">
            <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted fs-7"></i>
            <input type="text" class="form-control form-control-sm bg-light border-0 rounded-pill ps-5 py-2 shadow-sm transition" id="globalSearchBar" name="global_search" placeholder="Cari data pokok..." aria-label="Cari data pokok..." style="font-size:0.8rem;">
        </div>
    </div>

    <!-- Right-side User Dropdown Actions -->
    <div class="d-flex align-items-center gap-2 gap-md-4 ms-2">
        <!-- Viewport Mode Toggle Switch -->
        <button class="btn btn-light text-secondary btn-sm rounded-pill px-3 py-2 fs-8 d-none d-sm-flex align-items-center gap-2 shadow-sm transition hover-lift border-0" id="btnToggleViewport" onclick="toggleViewportMode()" title="Ganti Mode Tampilan (Desktop / Mobile)">
            <span id="txtViewportMode" class="text-nowrap fw-medium"><i class="bi bi-phone"></i> Mobile</span>
        </button>

        <!-- Real-time Digital Clock (Premium Aesthetic) -->
        <div class="d-none d-lg-flex align-items-center bg-white px-3 py-2 rounded-pill shadow-sm border text-muted fw-medium fs-8 transition hover-lift text-nowrap" id="header-clock-container" style="font-size: 0.75rem;">
            <i class="bi bi-clock text-primary me-2 pulse-anim"></i>
            <span id="header-clock" class="font-monospace text-dark text-nowrap">00:00:00</span>
        </div>

        <!-- Notification Bell (Visual Only) -->
        <button class="btn btn-light text-secondary position-relative p-2 rounded-circle border-0 d-flex align-items-center justify-content-center shadow-sm hover-lift transition" style="width: 40px; height: 40px;">
            <i class="bi bi-bell fs-5"></i>
            <span class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-2 border-white rounded-circle" style="margin-top: 5px; margin-left: -10px;">
                <span class="visually-hidden">New alerts</span>
            </span>
        </button>

        <!-- Divider -->
        <div class="vr bg-secondary opacity-25 d-none d-md-block" style="height: 30px;"></div>

        <!-- User Meta & Dropdown -->
        <div class="dropdown d-flex align-items-center gap-2 cursor-pointer hover-lift transition" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false" role="button">
            <div class="user-meta text-end d-none d-md-block">
                <div class="fw-bold text-dark fs-7 text-nowrap"><?= htmlspecialchars($_SESSION['nama_lengkap'] ?? 'Admin Utama') ?></div>
                <div class="text-primary text-uppercase" style="font-size: 0.65rem; font-weight: 800; letter-spacing: 0.5px;"><?= htmlspecialchars(str_replace('_', ' ', $_SESSION['role_name'] ?? 'operator_sekolah')) ?></div>
            </div>
            <div class="bg-gradient-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold shadow position-relative" style="width: 42px; height: 42px; font-size: 1.1rem; background: linear-gradient(135deg, #0d6efd, #0dcaf0);">
                <?= substr(htmlspecialchars($_SESSION['nama_lengkap'] ?? 'A'), 0, 1) ?>
                <span class="position-absolute bottom-0 end-0 p-1 bg-success border border-2 border-white rounded-circle" style="transform: translate(20%, 20%);"></span>
            </div>
        </div>
        
        <!-- Dropdown Menu -->
        <ul class="dropdown-menu dropdown-menu-end border-0 shadow-lg mt-3 py-0 overflow-hidden rounded-3" aria-labelledby="userDropdown" style="min-width: 250px;">
            <li class="px-4 py-3 bg-light border-bottom">
                <div class="fw-bold fs-6 text-dark"><?= htmlspecialchars($_SESSION['nama_lengkap'] ?? 'Admin Utama') ?></div>
                <div class="text-muted" style="font-size: 0.8rem;"><?= htmlspecialchars($_SESSION['email'] ?? 'admin@sch.id') ?></div>
            </li>
            <li class="py-1">
                <?php if (($_SESSION['role_name'] ?? '') === 'siswa'): ?>
                    <a class="dropdown-item py-2 px-4 fw-medium text-secondary d-flex align-items-center gap-3 transition hover-bg-light" href="<?= $this->getBaseUrl() ?>/pengguna"><i class="bi bi-person fs-5"></i> Profil Saya</a>
                <?php else: ?>
                    <a class="dropdown-item py-2 px-4 fw-medium text-secondary d-flex align-items-center gap-3 transition hover-bg-light" href="#" onclick="showSimulationAlert('Profil Saya'); return false;"><i class="bi bi-person fs-5"></i> Profil Saya</a>
                <?php endif; ?>
            </li>
            <li>
                <a class="dropdown-item py-2 px-4 fw-medium text-secondary d-flex align-items-center gap-3 transition hover-bg-light" href="#" onclick="showSimulationAlert('Keamanan'); return false;"><i class="bi bi-shield-lock fs-5"></i> Keamanan</a>
            </li>
            <li class="border-top mt-1">
                <form action="<?= ($_SESSION['role_name'] ?? '') === 'siswa' ? $this->getBaseUrl() . '/siswa/logout' : $this->getBaseUrl() . '/api/v1/auth/logout' ?>" method="<?= ($_SESSION['role_name'] ?? '') === 'siswa' ? 'GET' : 'POST' ?>" class="m-0" id="logoutForm">
                    <button type="submit" class="dropdown-item py-3 px-4 text-danger fw-bold d-flex align-items-center gap-3 transition hover-bg-light bg-white w-100 text-start border-0">
                        <i class="bi bi-box-arrow-right fs-5"></i> Keluar Aplikasi
                    </button>
                </form>
            </li>
        </ul>
    </div>
</header>

<script>
    function initHeaderClock() {
        const clockEl = document.getElementById('header-clock');
        if (clockEl) {
            function updateClock() {
                const now = new Date();
                
                // Format Hari dan Tanggal Indonesia
                const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
                const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
                
                const dayName = days[now.getDay()];
                const date = String(now.getDate()).padStart(2, '0');
                const monthName = months[now.getMonth()];
                const year = now.getFullYear();
                
                const hours = String(now.getHours()).padStart(2, '0');
                const minutes = String(now.getMinutes()).padStart(2, '0');
                const seconds = String(now.getSeconds()).padStart(2, '0');
                
                clockEl.textContent = `${dayName}, ${date} ${monthName} ${year} • ${hours}:${minutes}:${seconds}`;
            }
            updateClock();
            if (window.headerClockInterval) {
                clearInterval(window.headerClockInterval);
            }
            window.headerClockInterval = setInterval(updateClock, 1000);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initHeaderClock);
    } else {
        initHeaderClock();
    }

    // =========================================================================
    // GLOBAL FRONTEND TELEMETRY TRACKER
    // Mengumpulkan error JavaScript, Unhandled Promises, & kegagalan AJAX (Axios)
    // dan mengirimkannya ke Dashboard Error Monitor secara diam-diam.
    // =========================================================================
    (function() {
        // Jangan melacak jika sedang di halaman error monitor untuk mencegah infinite loop
        if (window.location.pathname.includes('/error-monitor')) return;

        function getTelemetryContext(vueVm = null, vueInfo = null) {
            let connection = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
            let ctx = {
                url: window.location.href,
                viewport: `${window.innerWidth}x${window.innerHeight}`,
                online: navigator.onLine,
                connection_type: connection ? connection.effectiveType : 'unknown',
                language: navigator.language,
                timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
                time: new Date().toISOString()
            };
            if (vueVm) {
                // Ekstrak info komponen Vue secara dangkal
                ctx.vue_component = vueVm.$options ? vueVm.$options.name || 'AnonymousComponent' : 'Unknown';
                ctx.vue_lifecycle = vueInfo || 'unknown';
            }
            return ctx;
        }

        function logErrorToBackend(errorData) {
            // Gunakan fetch dengan keepalive atau sendBeacon agar tetap terkirim meskipun halaman ditutup
            const payload = JSON.stringify(errorData);
            if (navigator.sendBeacon) {
                navigator.sendBeacon('<?= $this->getBaseUrl() ?>/api/v1/error-monitor/log-client', payload);
            } else {
                fetch('<?= $this->getBaseUrl() ?>/api/v1/error-monitor/log-client', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: payload,
                    keepalive: true
                }).catch(() => {});
            }
        }

        // 1. Tangkap JS Runtime Errors
        window.onerror = function(message, source, lineno, colno, error) {
            const stackTrace = error && error.stack ? error.stack.split('\n').map(s => s.trim()) : [];
            logErrorToBackend({
                type: 'JS_ERROR',
                message: message,
                file: source,
                line: lineno,
                url: window.location.href,
                trace: stackTrace,
                context: getTelemetryContext()
            });
            return false; // biarkan default console.error tetap jalan
        };

        // 2. Tangkap Unhandled Promise Rejections (e.g., Axios gagal tanpa try/catch)
        window.addEventListener('unhandledrejection', function(event) {
            let msg = 'Unhandled Promise Rejection';
            let stack = [];
            let file = '';
            
            if (event.reason) {
                if (event.reason.message) msg = event.reason.message;
                else if (typeof event.reason === 'string') msg = event.reason;
                
                if (event.reason.stack) {
                    stack = event.reason.stack.split('\n').map(s => s.trim());
                }
                
                // Deteksi khusus jika ini Axios Error
                if (event.reason.isAxiosError) {
                    msg = `[AXIOS API ERROR] Status: ${(event.reason.response && event.reason.response.status) || 'Network Error'} - ${msg}`;
                    if (event.reason.config) {
                        stack.unshift(`Request URL: ${event.reason.config.url}`);
                    }
                }
            }
            
            logErrorToBackend({
                type: 'PROMISE_ERROR',
                message: msg,
                file: window.location.href, // sulit mendapatkan file asal di promise, pakai url saja
                line: 0,
                url: window.location.href,
                trace: stack,
                context: getTelemetryContext()
            });
        });

        // 3. Tangkap Vue Global Errors (Jika Vue ada dan mendukung global config)
        if (typeof window.Vue !== 'undefined' && window.Vue.config) {
            window.Vue.config.errorHandler = function(err, vm, info) {
                logErrorToBackend({
                    type: 'VUE_ERROR',
                    message: err.message,
                    file: window.location.href,
                    line: 0,
                    url: window.location.href,
                    trace: err.stack ? err.stack.split('\n').map(s => s.trim()) : [info],
                    context: getTelemetryContext(vm, info)
                });
                console.error(err);
            };
        }
    })();
</script>

<style>
/* Header Refactor Styles */
.hover-lift {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.hover-lift:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1) !important;
}
.transition {
    transition: all 0.3s ease;
}
.hover-bg-gray:hover {
    background-color: #f1f3f5 !important;
}
.hover-bg-light:hover {
    background-color: #f8f9fa !important;
}
@keyframes pulse {
    0% { transform: scale(1); opacity: 1; }
    50% { transform: scale(1.1); opacity: 0.7; }
    100% { transform: scale(1); opacity: 1; }
}
.pulse-anim {
    animation: pulse 2s infinite;
}
</style>

