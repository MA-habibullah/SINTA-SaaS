<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($data['title'] ?? ($stats['title'] ?? 'SINTA-SaaS SaaS')) ?></title>
    
    <!-- JS Error Tracker: Persists errors to LocalStorage for debugging -->
    <script data-turbo-track="reload">
    (function() {
        const STORAGE_KEY = 'sinta_js_errors';
        function saveError(type, message, source, lineno, colno, error) {
            let errors = JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]');
            errors.push({
                type: type, time: new Date().toLocaleTimeString(),
                message: message, source: source, lineno: lineno,
                stack: error ? error.stack : ''
            });
            if (errors.length > 30) errors = errors.slice(-30);
            localStorage.setItem(STORAGE_KEY, JSON.stringify(errors));
            showErrorBadge();

            // Send to Backend Error Monitor
            const payload = JSON.stringify({
                type: type, message: message, source: source, lineno: lineno, stack: error ? error.stack : ''
            });
            if (navigator.sendBeacon) {
                navigator.sendBeacon('/SINTA-SaaS/api/v1/log-js-error', payload);
            } else {
                fetch('/SINTA-SaaS/api/v1/log-js-error', { method: 'POST', body: payload, keepalive: true }).catch(e=>{});
            }
        }
        window.addEventListener('error', function(e) {
            // Ignore CORS "Script error." (usually from browser extensions or adblockers)
            if (e.message === 'Script error.' && e.lineno === 0) return;
            saveError('Error', e.message, e.filename, e.lineno, e.colno, e.error);
        });
        window.addEventListener('unhandledrejection', function(e) {
            saveError('Promise Rejection', e.reason ? (e.reason.message || e.reason) : 'Unknown', '', 0, 0, e.reason);
        });
        function showErrorBadge() {
            let errors = JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]');
            if (errors.length === 0) return;
            let badge = document.getElementById('js-error-badge');
            if (!badge) {
                badge = document.createElement('div');
                badge.id = 'js-error-badge';
                badge.style.cssText = 'position:fixed;bottom:20px;left:20px;background:#e11d48;color:white;padding:10px 15px;border-radius:8px;z-index:99999;cursor:pointer;font-family:system-ui,sans-serif;box-shadow:0 10px 15px -3px rgba(0,0,0,0.3);display:flex;align-items:center;gap:12px;font-weight:600;font-size:14px;border:1px solid #be123c;';
                badge.innerHTML = `⚠️ <span id="js-error-count">${errors.length}</span> JS Errors <span style="font-size:11px;background:rgba(255,255,255,0.25);padding:3px 8px;border-radius:6px;" id="js-error-clear">Clear</span>`;
                if(document.body) document.body.appendChild(badge);
                
                badge.addEventListener('click', function(e) {
                    if (e.target.id === 'js-error-clear') {
                        localStorage.removeItem(STORAGE_KEY);
                        badge.remove();
                        return;
                    }
                    let errList = JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]');
                    let html = '<div style="max-height:60vh;overflow-y:auto;text-align:left;font-family:monospace;font-size:13px;background:#1e293b;color:#f8fafc;padding:15px;border-radius:8px;">';
                    errList.reverse().forEach(err => {
                        html += `<div style="margin-bottom:15px;padding-bottom:15px;border-bottom:1px solid #334155;">
                            <strong style="color:#f87171;">[${err.time}] ${err.type}</strong><br>
                            <span style="color:#f1f5f9;">${err.message}</span><br>
                            <small style="color:#94a3b8;">${err.source ? err.source + ':' + err.lineno : ''}</small><br>
                            <div style="background:#0f172a;padding:10px;margin-top:8px;overflow-x:auto;white-space:pre;color:#a5b4fc;">${err.stack || 'No stack trace'}</div>
                        </div>`;
                    });
                    html += '</div>';

                    if (window.Swal) {
                        Swal.fire({ title: 'JavaScript Error Log', html: html, width: '800px', confirmButtonText: 'Tutup' });
                    } else {
                        let w = window.open('', '_blank', 'width=800,height=600');
                        w.document.write('<html style="background:#0f172a;color:white;"><body style="font-family:sans-serif;padding:20px;"><h2>JS Errors</h2>' + html + '</body></html>');
                    }
                });
            } else {
                let cnt = document.getElementById('js-error-count');
                if(cnt) cnt.innerText = errors.length;
            }
        }
        window.addEventListener('DOMContentLoaded', showErrorBadge);
        document.addEventListener('turbo:load', showErrorBadge);
    })();
    </script>

    <!-- Google Fonts: Plus Jakarta Sans & Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet" data-turbo-track="reload">
    
    <!-- Bootstrap 5.3 CSS -->
    <link href="/SINTA-SaaS/assets/css/bootstrap.min.css" rel="stylesheet" data-turbo-track="reload">
    
    <!-- Bootstrap Icons -->
    <link href="/SINTA-SaaS/assets/css/bootstrap-icons.css" rel="stylesheet" data-turbo-track="reload">
    
    <!-- jQuery 3.7.0 -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js" data-turbo-track="reload"></script>
    
    <!-- Bootstrap Bundle with Popper -->
    <script src="/SINTA-SaaS/assets/js/bootstrap.bundle.min.js" data-turbo-track="reload"></script>
    
    <!-- Vue 3 Global Build -->
    <script src="/SINTA-SaaS/assets/js/vue.global.prod.js" data-turbo-track="reload"></script>
    
    <!-- Axios (API Requests client) -->
    <script src="/SINTA-SaaS/assets/js/axios.min.js" data-turbo-track="reload"></script>
    
    <!-- Tailwind CSS (Play CDN) with Preflight disabled to prevent conflicts with Bootstrap -->
    <script data-turbo-track="reload">
        (function() {
            const origWarn = console.warn;
            console.warn = function(...args) {
                if (typeof args[0] === 'string' && args[0].includes('cdn.tailwindcss.com')) return;
                origWarn.apply(console, args);
            };
        })();

        window.tailwind = {
            config: {
                corePlugins: {
                    preflight: false,
                },
                theme: {
                    extend: {
                        colors: {
                            'saas-blue': '#2563eb',
                            'saas-hover': '#1d4ed8',
                            'saas-light': '#eff6ff',
                        }
                    }
                }
            }
        };
    </script>
    <script src="/SINTA-SaaS/assets/js/tailwindcss.js" data-turbo-track="reload"></script>

    <!-- Hotwire Turbo Drive -->
    <script src="/SINTA-SaaS/assets/js/turbo.es2017-umd.js" defer data-turbo-track="reload"></script>
    
    <!-- SweetAlert2 (Loaded globally to support all pages offline) -->
    <script src="/SINTA-SaaS/assets/js/sweetalert2.all.min.js" data-turbo-track="reload"></script>
    
    <!-- Chart.js (Loaded globally to support offline graphs without race conditions) -->
    <script src="/SINTA-SaaS/assets/js/chart.umd.js" data-turbo-track="reload"></script>
    
    <!-- Vue 3 Lifecycle Registry and Turbo Drive Integration -->
    <script>
        window.vueApps = window.vueApps || {};

        // Debug Mode: Global Axios Interceptor
        if (window.axios) {
            axios.interceptors.response.use(
                response => response,
                error => {
                    const status = error.response ? error.response.status : null;
                    if (status === 401) {
                        window.location.href = '/SINTA-SaaS/login';
                        return new Promise(() => {}); // Gantung request agar tidak memicu error modal/alert lain di halaman
                    }
                    const statusText = status || 'Network / Timeout Error';
                    const data = error.response ? error.response.data : 'Tidak ada data payload';
                    console.error(
                        '%c[AXIOS API ERROR] Status: ' + statusText, 
                        'background: #dc2626; color: white; font-size: 14px; font-weight: bold; padding: 4px; border-radius: 4px;',
                        '\nPayload Response:', data,
                        '\nRequest Config:', error.config
                    );
                    alert('API Error: ' + statusText + '\n(Periksa Developer Console Chrome untuk detail data payload)');
                    return Promise.reject(error);
                }
            );
        }

        window.showSimulationAlert = function(featureName) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: '<div class="fs-6 fw-bold border-bottom pb-2 text-start text-dark">Simulasi Fitur</div>',
                    html: `
                        <div class="text-start fs-7 text-muted my-2">
                            Fitur <strong>"${featureName}"</strong> saat ini masih dalam mode simulasi/pengembangan dan belum dihubungkan ke data riil.
                        </div>
                    `,
                    icon: 'info',
                    confirmButtonColor: '#2563eb',
                    confirmButtonText: 'Tutup'
                });
            } else {
                console.info(`Fitur simulasi: ${featureName}`);
            }
        };

        window.VueAppRegistry = {
            registry: [],
            register(selector, appConfig, configureFn = null) {
                const index = this.registry.findIndex(item => item.selector === selector);
                if (index !== -1) {
                    this.registry[index] = { selector, appConfig, configureFn };
                } else {
                    this.registry.push({ selector, appConfig, configureFn });
                }
            },
            mountAll() {
                // Bersihkan aplikasi yatim jika ada sisa DOM yang terlepas
                this.cleanupOrphanedApps();

                this.registry.forEach(({ selector, appConfig, configureFn }) => {
                    const el = document.querySelector(selector);
                    if (el) {
                        const active = window.vueApps[selector];
                        
                        // Singleton: jika sudah di-mount pada DOM node yang sama, abaikan!
                        if (active && document.body.contains(active.el) && active.el === el) {
                            return;
                        }

                        // Jika ada instansi aktif pada DOM node berbeda (e.g. sisa halaman lama), bersihkan dulu
                        if (active) {
                            try {
                                active.app.unmount();
                            } catch (err) {
                                console.error(`[Vue Registry] Failed to unmount old instance for: ${selector}`, err);
                            }
                            delete window.vueApps[selector];
                        }

                        try {
                            const app = Vue.createApp(appConfig);

                            // Debug Mode: Global Vue 3 Error Handler
                            app.config.errorHandler = (err, instance, info) => {
                                console.error(
                                    '%c[VUE RUNTIME ERROR]', 
                                    'background: #ea580c; color: white; font-size: 14px; font-weight: bold; padding: 4px; border-radius: 4px;',
                                    err,
                                    '\nComponent Instance:', instance,
                                    '\nLifecycle Info / Source:', info
                                );
                            };

                            if (typeof configureFn === 'function') {
                                configureFn(app);
                            }
                            const instance = app.mount(el);
                            window.vueApps[selector] = {
                                app: app,
                                instance: instance,
                                el: el
                            };
                        } catch (err) {
                            console.error(`[Vue Registry] Failed to mount Vue app on: ${selector}`, err);
                        }
                    }
                });
            },
            unmountAll() {
                Object.keys(window.vueApps).forEach(selector => {
                    const active = window.vueApps[selector];
                    if (active) {
                        try {
                            active.app.unmount();
                        } catch (err) {
                            console.error(`[Vue Registry] Failed to unmount: ${selector}`, err);
                        }
                        delete window.vueApps[selector];
                    }
                });
            },
            cleanupOrphanedApps() {
                Object.keys(window.vueApps).forEach(selector => {
                    const active = window.vueApps[selector];
                    if (active) {
                        const currentEl = document.querySelector(selector);
                        // Hanya unmount jika elemen sudah lepas dari body DOM, atau digantikan oleh DOM node baru
                        if (!document.body.contains(active.el) || active.el !== currentEl) {
                            try {
                                active.app.unmount();
                            } catch (err) {
                                console.error(`[Vue Registry] Failed to unmount orphaned Vue app on: ${selector}`, err);
                            }
                            delete window.vueApps[selector];
                        }
                    }
                });
            }
        };

        // Integrasi dengan Lifecycle Turbo Drive
        document.addEventListener('turbo:load', function() {
            window.VueAppRegistry.mountAll();

            // Toggle Sidebar JS (Vanilla JS)
            const toggleBtn = document.getElementById('sidebarToggle');
            const wrapper = document.getElementById('layout-wrapper');
            const overlay = document.getElementById('sidebar-overlay');
            
            if (toggleBtn && wrapper) {
                toggleBtn.onclick = function(e) {
                    e.preventDefault();
                    // Desktop: Toggle collapsed state
                    if (window.innerWidth >= 992) {
                        wrapper.classList.toggle('sidebar-collapsed');
                        
                        // Simpan status toggle di localStorage agar persisten saat berpindah halaman
                        if (wrapper.classList.contains('sidebar-collapsed')) {
                            localStorage.setItem('sidebarState', 'collapsed');
                        } else {
                            localStorage.setItem('sidebarState', 'expanded');
                        }
                    } 
                    // Mobile: Toggle open/close sidebar overlay
                    else {
                        wrapper.classList.toggle('sidebar-open');
                    }
                };
            }

            // Close sidebar when clicking backdrop on mobile
            if (overlay && wrapper) {
                overlay.onclick = function() {
                    wrapper.classList.remove('sidebar-open');
                };
            }

            // Load sidebar state from localStorage on page load
            if (window.innerWidth >= 992 && wrapper) {
                const savedState = localStorage.getItem('sidebarState');
                if (savedState === 'collapsed') {
                    wrapper.classList.add('sidebar-collapsed');
                } else {
                    wrapper.classList.remove('sidebar-collapsed');
                }
            }
        });

        document.addEventListener('turbo:before-cache', function() {
            // 1. Unmount ALL active Vue 3 instances
            if (window.vueApps) {
                Object.keys(window.vueApps).forEach(function(selector) {
                    const active = window.vueApps[selector];
                    if (active && active.app && typeof active.app.unmount === 'function') {
                        try {
                            active.app.unmount();
                        } catch (err) {
                            console.error(`[Vue Cleanup] Failed to unmount: ${selector}`, err);
                        }
                    }
                });
                window.vueApps = {};
            }

            // Fallback unmount via registry if exists
            if (window.VueAppRegistry && typeof window.VueAppRegistry.unmountAll === 'function') {
                try {
                    window.VueAppRegistry.unmountAll();
                } catch (err) {}
            }

            // 2. Destroy Chart.js instances
            if (typeof Chart !== 'undefined' && Chart.instances) {
                if (Chart.helpers && typeof Chart.helpers.each === 'function') {
                    try {
                        Chart.helpers.each(Chart.instances, function(instance) {
                            try {
                                instance.destroy();
                            } catch (e) {
                                console.warn('[Chart.js Cleanup] Failed to destroy instance:', e);
                            }
                        });
                    } catch (e) {
                        console.warn('[Chart.js Cleanup] Helper each failed:', e);
                    }
                } else {
                    Object.keys(Chart.instances).forEach(function(key) {
                        try {
                            Chart.instances[key].destroy();
                        } catch (e) {
                            console.warn('[Chart.js Cleanup] Failed to destroy instance key:', key, e);
                        }
                    });
                }
            }

            // 3. Destroy ApexCharts instances if any exist
            if (typeof ApexCharts !== 'undefined' && window.Apex && window.Apex._currentCharts) {
                try {
                    window.Apex._currentCharts.forEach(function(chart) {
                        if (chart && typeof chart.destroy === 'function') {
                            chart.destroy();
                        }
                    });
                } catch (e) {
                    console.warn('[ApexCharts Cleanup] Failed to destroy ApexCharts:', e);
                }
            }

            // 4. Destroy DataTables if jQuery & DataTables plugin are initialized
            if (typeof $ !== 'undefined' && $.fn && $.fn.DataTable) {
                try {
                    if (typeof $.fn.DataTable.isDataTable === 'function') {
                        $('.dataTable, table.display, table').each(function() {
                            if ($.fn.DataTable.isDataTable(this)) {
                                $(this).DataTable().destroy();
                            }
                        });
                    } else {
                        $('.dataTable').DataTable().destroy();
                    }
                } catch (e) {
                    console.warn('[DataTables Cleanup] Failed to destroy DataTables:', e);
                }
            }
        });

        // Failsafe triggers untuk inisialisasi awal / non-Turbo visits
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                window.VueAppRegistry.mountAll();
            });
        } else {
            window.VueAppRegistry.mountAll();
        }

        window.addEventListener('load', function() {
            window.VueAppRegistry.mountAll();
        });
    </script>
    
    <style>
        :root {
            --primary-blue: #2563eb;       /* Biru Modern */
            --primary-hover: #1d4ed8;      /* Biru Gelap Hover */
            --bg-body: #f8fafc;            /* Abu-abu Sangat Terang */
            --text-main: #1e293b;          /* Slate 800 */
            --text-dark: #0f172a;          /* Slate 900 */
            --text-muted: #64748b;         /* Slate 500 */
            --sidebar-width: 260px;
            --sidebar-collapsed-width: 70px;
            --header-height: 70px;
        }

        body {
            font-family: 'Plus Jakarta Sans', 'Inter', sans-serif;
            background-color: var(--bg-body);
            color: var(--text-main);
            overflow-x: hidden;
        }

        /* 1. Layout Wrapper */
        #layout-wrapper {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            position: relative;
        }

        /* 2. Header Navbar (Putih Bersih dengan Shadow Tipis) */
        .navbar-custom {
            background-color: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.05);
            height: var(--header-height);
            z-index: 1030;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            transition: all 0.3s ease;
        }

        /* Global Search */
        .search-bar-container {
            position: relative;
            max-width: 280px;
        }

        .search-bar-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 0.85rem;
            pointer-events: none;
        }

        .search-bar-input {
            padding-left: 36px;
            background-color: #f1f5f9;
            border: 1px solid transparent;
            border-radius: 20px;
            font-size: 0.85rem;
            height: 38px;
            transition: all 0.3s ease;
        }

        .search-bar-input:focus {
            background-color: #ffffff;
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
        }

        /* 3. Sidebar (Putih Bersih, Border Halus, Font Elegan) */
        #sidebar {
            background-color: #ffffff;
            border-right: 1px solid #e2e8f0;
            width: var(--sidebar-width);
            position: fixed;
            top: var(--header-height);
            bottom: 0;
            left: 0;
            z-index: 1020;
            transition: all 0.3s ease;
            box-shadow: 2px 0 8px rgba(0, 0, 0, 0.015);
        }

        .nav-link-item {
            display: flex;
            align-items: center;
            padding: 0.6rem 1rem;
            color: #495057;
            text-decoration: none;
            font-size: 0.875rem; /* Font Size Elegan (14px) */
            font-weight: 500;
            border-radius: 8px;
            transition: all 0.2s ease;
            border-left: 4px solid transparent;
            margin-bottom: 0.15rem;
        }

        .nav-link-item i {
            font-size: 1.15rem;
            margin-right: 12px;
            width: 20px;
            text-align: center;
            color: #64748b;
            transition: all 0.2s ease;
        }

        /* Hover & Active State: Biru Modern (#2563eb) */
        .nav-link-item:hover {
            color: var(--primary-blue);
            background-color: #eff6ff; /* Biru Sangat Muda */
        }

        .nav-link-item:hover i {
            color: var(--primary-blue);
        }

        .nav-link-item.active {
            color: var(--primary-blue);
            background-color: #eff6ff;
            font-weight: 600;
            border-left-color: var(--primary-blue);
        }

        .nav-link-item.active i {
            color: var(--primary-blue);
        }

        /* Group Headers Nav */
        .nav-group-header {
            font-size: 0.7rem;
            font-weight: 700;
            color: #94a3b8;
            letter-spacing: 0.8px;
            text-transform: uppercase;
        }

        /* 4. Main Content Area (Light Gray) */
        #main-content {
            margin-left: var(--sidebar-width);
            margin-top: var(--header-height);
            min-height: calc(100vh - var(--header-height));
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            background-color: var(--bg-body);
            flex-grow: 1;
            min-width: 0;
        }

        .content-body {
            padding: 2rem;
            flex-grow: 1;
        }

        /* 5. Footer (Putih/Light Gray, Sticky) */
        .footer {
            background-color: #ffffff;
            border-top: 1px solid #e2e8f0;
            z-index: 1010;
        }

        /* 6. COLLAPSED STATE (Desktop Toggle) */
        @media (min-width: 992px) {
            #layout-wrapper.sidebar-collapsed #sidebar {
                width: var(--sidebar-collapsed-width);
            }
            
            #layout-wrapper.sidebar-collapsed #sidebar .nav-label {
                display: none !important;
            }

            #layout-wrapper.sidebar-collapsed #sidebar .nav-group-header {
                text-align: center;
                padding: 0.5rem 0 !important;
            }

            #layout-wrapper.sidebar-collapsed #sidebar .nav-group-header span {
                display: none !important;
            }

            #layout-wrapper.sidebar-collapsed #sidebar .nav-group-header::after {
                content: "";
                display: block;
                width: 20px;
                height: 1px;
                background-color: #e2e8f0;
                margin: 4px auto;
            }

            #layout-wrapper.sidebar-collapsed #sidebar .sidebar-footer {
                display: none !important;
            }

            #layout-wrapper.sidebar-collapsed #main-content {
                margin-left: var(--sidebar-collapsed-width);
            }
        }

        /* 7. RESPONSIVE SIDEBAR MOBILE (Overlay Behavior) */
        @media (max-width: 991.98px) {
            #sidebar {
                left: calc(var(--sidebar-width) * -1);
                top: 0;
                height: 100vh;
                z-index: 1040;
            }

            #main-content {
                margin-left: 0 !important;
            }

            #layout-wrapper.sidebar-open #sidebar {
                left: 0;
            }

            /* Overlay backdrop */
            #sidebar-overlay {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background-color: rgba(15, 23, 42, 0.4);
                z-index: 1035;
                backdrop-filter: blur(2px);
                transition: opacity 0.3s ease;
            }

            #layout-wrapper.sidebar-open #sidebar-overlay {
                display: block;
            }
        }
    </style>
</head>
<body>

<div id="layout-wrapper">
    <!-- Overlay Backdrop for Mobile -->
    <div id="sidebar-overlay"></div>

    <!-- Header Component -->
    <?php include __DIR__ . '/header.php'; ?>

    <!-- Outer Flex Container for Sidebar + Dynamic Content -->
    <div class="d-flex flex-grow-1 w-100">
        
        <!-- Sidebar Component -->
        <?php include __DIR__ . '/sidebar.php'; ?>

        <!-- Dynamic Content Body Section -->
        <div id="main-content">
            <main id="app-content" class="content-body">
                <?php 
                if (isset($contentView) && file_exists($contentView)) {
                    require $contentView;
                } else {
                    echo "<div class='alert alert-danger'>Kesalahan: File view tidak ditemukan.</div>";
                }
                ?>
            </main>

            <!-- Footer Component -->
            <?php include __DIR__ . '/footer.php'; ?>
        </div>

    </div>
</div>


</body>
</html>
