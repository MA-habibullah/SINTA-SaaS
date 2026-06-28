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
        $stmt = $db->prepare("SELECT nama_sekolah, npsn FROM tenants WHERE id = :id AND deleted_at IS NULL LIMIT 1");
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
<header id="app-header" class="navbar navbar-expand navbar-custom sticky-top px-3" data-turbo-permanent>
    <div class="d-flex align-items-center flex-grow-1 gap-2">
        <!-- Burger Button Toggle (Vanilla JS/Vue) -->
        <button class="btn btn-link text-dark border-0 p-2 me-1" id="sidebarToggle" type="button" aria-label="Toggle Sidebar">
            <i class="bi bi-list fs-4"></i>
        </button>

        <!-- Brand Logo / Name -->
        <a class="navbar-brand d-flex align-items-center text-decoration-none me-4" href="/SINTA-SaaS/dashboard">
            <i class="bi bi-mortarboard-fill text-primary fs-3 me-2"></i>
            <span class="fw-extrabold fs-5 text-dark tracking-tight d-none d-sm-inline">DAPODIK <span class="text-primary">SAAS</span></span>
        </a>

        <!-- Indikator Tenant (Nama Sekolah Aktif) -->
        <div class="tenant-indicator d-flex align-items-center bg-light px-3 py-1.5 rounded-pill border me-auto">
            <i class="bi bi-building-fill text-primary me-2 fs-7"></i>
            <div class="fs-8 fw-semibold text-dark text-truncate" style="max-width: 250px;">
                <?= htmlspecialchars($namaSekolah) ?> 
                <span class="text-muted fw-normal d-none d-md-inline">(NPSN: <?= htmlspecialchars($npsnSekolah) ?>)</span>
            </div>
        </div>

        <!-- Global Search Bar -->
        <div class="search-bar-container d-none d-lg-block me-3">
            <i class="bi bi-search search-bar-icon"></i>
            <input type="text" class="form-control form-control-sm search-bar-input" placeholder="Cari data pokok...">
        </div>
    </div>

    <!-- Right-side User Dropdown Actions -->
    <div class="d-flex align-items-center gap-3">
        <!-- Real-time Digital Clock (Premium Aesthetic) -->
        <div class="d-none d-lg-flex align-items-center bg-light px-3 py-1.5 rounded-pill border text-muted fw-medium fs-8" id="header-clock-container" style="font-size: 0.775rem;">
            <i class="bi bi-clock-fill text-primary me-2"></i>
            <span id="header-clock" class="font-monospace text-dark">00:00:00</span>
        </div>

        <!-- Notification Bell (Visual Only) -->
        <button class="btn btn-link text-muted position-relative p-2 border-0 d-none d-sm-block">
            <i class="bi bi-bell fs-5"></i>
            <span class="position-absolute top-1 start-50 translate-middle-y p-1 bg-danger border border-light rounded-circle"></span>
        </button>

        <div class="user-meta text-end d-none d-md-block">
            <div class="fw-semibold text-dark fs-7"><?= htmlspecialchars($_SESSION['nama_lengkap'] ?? 'Admin Utama') ?></div>
            <div class="text-muted fs-8 text-uppercase" style="font-size: 0.7rem; font-weight: 700; letter-spacing: 0.5px;"><?= htmlspecialchars(str_replace('_', ' ', $_SESSION['role_name'] ?? 'operator_sekolah')) ?></div>
        </div>

        <!-- Dropdown Profil User -->
        <div class="dropdown">
            <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold shadow-sm" style="width: 38px; height: 38px; font-size: 1rem;">
                    <?= substr(htmlspecialchars($_SESSION['nama_lengkap'] ?? 'A'), 0, 1) ?>
                </div>
            </a>
            <ul class="dropdown-menu dropdown-menu-end border-0 shadow mt-2" aria-labelledby="userDropdown">
                <li class="px-3 py-2 text-dark bg-light rounded-top">
                    <div class="fw-bold fs-7"><?= htmlspecialchars($_SESSION['nama_lengkap'] ?? 'Admin Utama') ?></div>
                    <div class="text-muted" style="font-size: 0.75rem;"><?= htmlspecialchars($_SESSION['email'] ?? 'admin@sch.id') ?></div>
                </li>
                <li><hr class="dropdown-divider my-1"></li>
                <?php if (($_SESSION['role_name'] ?? '') === 'siswa'): ?>
                    <li><a class="dropdown-item py-2" href="/SINTA-SaaS/siswa/edit?id=<?= htmlspecialchars($_SESSION['user_id'] ?? '') ?>"><i class="bi bi-person me-2 fs-7"></i>Profil Saya</a></li>
                <?php else: ?>
                    <li><a class="dropdown-item py-2" href="#" onclick="showSimulationAlert('Profil Saya'); return false;"><i class="bi bi-person me-2 fs-7"></i>Profil Saya</a></li>
                <?php endif; ?>
                <li><a class="dropdown-item py-2" href="#" onclick="showSimulationAlert('Keamanan'); return false;"><i class="bi bi-shield-lock me-2 fs-7"></i>Keamanan</a></li>
                <li><hr class="dropdown-divider my-1"></li>
                <li>
                    <form action="<?= ($_SESSION['role_name'] ?? '') === 'siswa' ? '/SINTA-SaaS/siswa/logout' : '/SINTA-SaaS/api/v1/auth/logout' ?>" method="<?= ($_SESSION['role_name'] ?? '') === 'siswa' ? 'GET' : 'POST' ?>" class="m-0" id="logoutForm">
                        <button type="submit" class="dropdown-item py-2 text-danger fw-semibold d-flex align-items-center">
                            <i class="bi bi-box-arrow-right me-2 fs-7"></i>Keluar Aplikasi
                        </button>
                    </form>
                </li>
            </ul>
        </div>
    </div>
</header>

<script>
    document.addEventListener('DOMContentLoaded', function() {
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
            setInterval(updateClock, 1000);
        }
    });

    // =========================================================================
    // GLOBAL FRONTEND TELEMETRY TRACKER
    // Mengumpulkan error JavaScript, Unhandled Promises, & kegagalan AJAX (Axios)
    // dan mengirimkannya ke Dashboard Error Monitor secara diam-diam.
    // =========================================================================
    (function() {
        // Jangan melacak jika sedang di halaman error monitor untuk mencegah infinite loop
        if (window.location.pathname.includes('/error-monitor')) return;

        function logErrorToBackend(errorData) {
            // Gunakan fetch dengan keepalive atau sendBeacon agar tetap terkirim meskipun halaman ditutup
            const payload = JSON.stringify(errorData);
            if (navigator.sendBeacon) {
                navigator.sendBeacon('/SINTA-SaaS/api/v1/error-monitor/log-client', payload);
            } else {
                fetch('/SINTA-SaaS/api/v1/error-monitor/log-client', {
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
                trace: stackTrace
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
                    msg = `[AXIOS API ERROR] Status: ${event.reason.response?.status || 'Network Error'} - ${msg}`;
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
                trace: stack
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
                    trace: err.stack ? err.stack.split('\n').map(s => s.trim()) : [info]
                });
                console.error(err);
            };
        }
    })();
</script>
