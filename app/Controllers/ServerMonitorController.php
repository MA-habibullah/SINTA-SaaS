<?php

namespace App\Controllers;

use App\Config\Database;
use App\Core\SessionManager;
use PDO;

/**
 * ServerMonitorController
 *
 * Dashboard monitoring kesehatan server secara global & resource per-tenant.
 * RBAC Ketat: Hanya super_admin.
 *
 * Routes:
 *   GET /super-admin/server-monitor                    → index()
 *   GET /api/v1/super-admin/server-monitor/fetch       → fetchApi()
 */
class ServerMonitorController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        SessionManager::requireLogin();

        if (($_SESSION['role_name'] ?? '') !== 'super_admin') {
            http_response_code(403);
            echo "<div style='font-family:sans-serif;text-align:center;padding:60px'>";
            echo "<h1 style='color:#dc3545;'>🔒 403 — Akses Ditolak</h1>";
            echo "<p style='color:#6c757d;'>Halaman Server Monitor hanya dapat diakses oleh <strong>Super Admin Platform</strong>.</p>";
            echo "<a href='/SINTA-SaaS/dashboard' style='display:inline-block;margin-top:1rem;padding:0.5rem 1.5rem;background:#2563eb;color:#fff;text-decoration:none;border-radius:6px;'>Kembali ke Dashboard</a>";
            echo "</div>";
            exit;
        }
    }

    /** GET /super-admin/server-monitor */
    public function index(): void
    {
        $this->render('server_monitor', [
            'title'     => 'Server & Resource Monitor',
            'user_role' => $_SESSION['role_name'] ?? '',
        ]);
    }

    /**
     * GET /api/v1/super-admin/server-monitor/fetch
     * Mengembalikan JSON: { global_metrics, tenants }
     */
    public function fetchApi(): void
    {
        try {
            $isLinux = PHP_OS_FAMILY === 'Linux';
            $this->jsonResponse([
                'success'            => true,
                'global_metrics'     => $this->getGlobalMetrics(),
                'tenants'            => $this->getTenantMetrics(),
                'network_interfaces' => $this->getNetworkInterfaces($isLinux),
                'timestamp'          => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            error_log("ServerMonitor fetchApi error: " . $e->getMessage());
            $this->jsonResponse(['error' => 'Gagal memuat metrik server.'], 500);
        }
    }

    // =========================================================================
    // GLOBAL SERVER METRICS
    // =========================================================================

    /**
     * Ambil metrik kesehatan server secara global.
     * Menggunakan fungsi Linux-native dengan fallback Windows yang aman.
     */
    private function getGlobalMetrics(): array
    {
        $isLinux   = PHP_OS_FAMILY === 'Linux';
        $isWindows = PHP_OS_FAMILY === 'Windows';

        return [
            'cpu'    => $this->getCpuMetrics($isLinux),
            'ram'    => $this->getRamMetrics($isLinux, $isWindows),
            'disk'   => $this->getDiskMetrics(),
            'uptime' => $this->getUptime($isLinux),
            'os'     => PHP_OS_FAMILY,
        ];
    }

    /** CPU: Load Average (Linux) atau N/A (Windows) */
    private function getCpuMetrics(bool $isLinux): array
    {
        $load1 = 0.0;
        $load5 = 0.0;
        $load15 = 0.0;
        $cpuCount = 1;
        $usagePercent = 0.0;

        if ($isLinux && function_exists('sys_getloadavg')) {
            [$load1, $load5, $load15] = sys_getloadavg();

            // Hitung jumlah CPU core
            if (is_readable('/proc/cpuinfo')) {
                $cpuinfoContent = file_get_contents('/proc/cpuinfo');
                $cpuCount = max(1, substr_count($cpuinfoContent, 'processor'));
            }

            // Normalisasi ke persentase (load / cores * 100)
            $usagePercent = min(100, round(($load1 / $cpuCount) * 100, 1));
        } elseif ($isLinux) {
            // Fallback: baca /proc/stat jika sys_getloadavg tidak tersedia
            $usagePercent = 0.0;
        }
        // Windows: sys_getloadavg() tidak tersedia, kembalikan nilai N/A

        return [
            'load_1'        => round($load1, 2),
            'load_5'        => round($load5, 2),
            'load_15'       => round($load15, 2),
            'cpu_count'     => $cpuCount,
            'usage_percent' => $usagePercent,
            'available'     => $isLinux,
        ];
    }

    /** RAM: Baca dari /proc/meminfo (Linux) atau Windows fallback */
    private function getRamMetrics(bool $isLinux, bool $isWindows): array
    {
        $totalMB = 0;
        $usedMB  = 0;
        $freeMB  = 0;
        $usagePercent = 0.0;

        if ($isLinux && is_readable('/proc/meminfo')) {
            $meminfo = file_get_contents('/proc/meminfo');
            preg_match('/MemTotal:\s+(\d+)\s+kB/', $meminfo, $totalMatch);
            preg_match('/MemAvailable:\s+(\d+)\s+kB/', $meminfo, $availMatch);

            if (!empty($totalMatch[1]) && !empty($availMatch[1])) {
                $totalKB = (int)$totalMatch[1];
                $availKB = (int)$availMatch[1];
                $usedKB  = $totalKB - $availKB;

                $totalMB = (int)round($totalKB / 1024);
                $usedMB  = (int)round($usedKB  / 1024);
                $freeMB  = (int)round($availKB  / 1024);
                $usagePercent = $totalMB > 0 ? round(($usedMB / $totalMB) * 100, 1) : 0.0;
            }
        } elseif ($isWindows) {
            // Windows: gunakan PHP memory_get_usage sebagai indikator proses ini
            // (tidak merepresentasikan seluruh sistem, tapi mencegah crash)
            $totalMB = 0;
            $usedMB  = (int)round(memory_get_usage(true) / 1024 / 1024);
            $freeMB  = 0;
            $usagePercent = 0.0;
        }

        return [
            'total_mb'      => $totalMB,
            'used_mb'       => $usedMB,
            'free_mb'       => $freeMB,
            'total_gb'      => round($totalMB / 1024, 2),
            'used_gb'       => round($usedMB  / 1024, 2),
            'free_gb'       => round($freeMB   / 1024, 2),
            'usage_percent' => $usagePercent,
            'available'     => $isLinux,
        ];
    }

    /** Disk: PHP native cross-platform (disk_total_space & disk_free_space) */
    private function getDiskMetrics(): array
    {
        $path = PHP_OS_FAMILY === 'Windows' ? 'C:\\' : '/';

        $totalBytes = disk_total_space($path);
        $freeBytes  = disk_free_space($path);

        if ($totalBytes === false || $freeBytes === false) {
            return [
                'total_gb' => 0, 'used_gb' => 0, 'free_gb' => 0,
                'usage_percent' => 0, 'available' => false,
            ];
        }

        $usedBytes = $totalBytes - $freeBytes;

        $totalGB = round($totalBytes / 1073741824, 2);
        $usedGB  = round($usedBytes  / 1073741824, 2);
        $freeGB  = round($freeBytes  / 1073741824, 2);
        $usagePercent = $totalBytes > 0 ? round(($usedBytes / $totalBytes) * 100, 1) : 0.0;

        return [
            'total_gb'      => $totalGB,
            'used_gb'       => $usedGB,
            'free_gb'       => $freeGB,
            'usage_percent' => $usagePercent,
            'available'     => true,
        ];
    }

    /** Uptime: baca /proc/uptime (Linux) */
    private function getUptime(bool $isLinux): array
    {
        $uptimeSeconds = 0;
        $uptimeHuman   = 'N/A (Windows)';

        if ($isLinux && is_readable('/proc/uptime')) {
            $contents      = file_get_contents('/proc/uptime');
            $parts         = explode(' ', $contents);
            $uptimeSeconds = (int)($parts[0] ?? 0);

            $days    = floor($uptimeSeconds / 86400);
            $hours   = floor(($uptimeSeconds % 86400) / 3600);
            $minutes = floor(($uptimeSeconds % 3600) / 60);

            $uptimeHuman = "{$days}d {$hours}h {$minutes}m";
        }

        return [
            'seconds'     => $uptimeSeconds,
            'human'       => $uptimeHuman,
            'available'   => $isLinux,
        ];
    }

    // =========================================================================
    // TENANT RESOURCE METRICS
    // =========================================================================

    /**
     * Ambil resource usage per-tenant: disk, active sessions, user count.
     */
    private function getTenantMetrics(): array
    {
        $db = Database::getConnection();

        // 1. Ambil semua tenant aktif beserta statistik dari DB
        $stmt = $db->query("
            SELECT
                t.id,
                t.nama_sekolah,
                t.npsn,
                t.status,
                t.paket_aktif,
                -- Jumlah staff aktif (di tabel users)
                (SELECT COUNT(*) FROM users u WHERE u.tenant_id = t.id AND u.deleted_at IS NULL) AS total_staff,
                -- Jumlah siswa aktif (di tabel siswa)
                (SELECT COUNT(*) FROM siswa s WHERE s.tenant_id = t.id AND s.deleted_at IS NULL) AS total_siswa,
                -- Sesi aktif: user yang last_activity dalam 15 menit terakhir
                (SELECT COUNT(DISTINCT s.user_id) FROM active_sessions s WHERE s.tenant_id = t.id AND s.last_activity >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)) AS active_sessions
            FROM tenants t
            WHERE t.deleted_at IS NULL
            ORDER BY t.nama_sekolah ASC
        ");

        $tenants = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 2. Kalkulasi ukuran direktori fisik per tenant
        $baseStoragePath = dirname(__DIR__, 2) . '/storage/app/public/uploads';

        foreach ($tenants as &$tenant) {
            $tenantDir = $baseStoragePath . '/' . $tenant['id'];
            $tenant['disk_mb']         = is_dir($tenantDir)
                ? round($this->calcDirectorySizeMB($tenantDir), 2)
                : 0.0;
            $tenant['total_users']     = (int)$tenant['total_staff'] + (int)$tenant['total_siswa'];
            $tenant['active_sessions'] = (int)$tenant['active_sessions'];
            $tenant['total_siswa']     = (int)$tenant['total_siswa'];
            $tenant['total_staff']     = (int)$tenant['total_staff'];

            // Quota status: berdasarkan disk usage riil dari StorageGuard
            $quotaBytes = \App\Core\StorageGuard::getTenantStorageLimit($tenant['id']);
            $quotaMB = round($quotaBytes / 1048576, 1); // bytes -> MB
            $tenant['quota_mb']      = $quotaMB;
            $tenant['quota_percent'] = $quotaMB > 0
                ? min(100, round(($tenant['disk_mb'] / $quotaMB) * 100, 1))
                : 0;

            // Label status quota
            if ($tenant['quota_percent'] >= 90) {
                $tenant['quota_status'] = 'Kritis';
            } elseif ($tenant['quota_percent'] >= 70) {
                $tenant['quota_status'] = 'Peringatan';
            } else {
                $tenant['quota_status'] = 'Normal';
            }
        }
        unset($tenant);

        return $tenants;
    }

    /**
     * Hitung total ukuran direktori secara rekursif (dalam MB).
     * Menggunakan RecursiveIteratorIterator yang efisien (non-shell).
     */
    private function calcDirectorySizeMB(string $dir): float
    {
        $bytes = 0;
        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );
            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $bytes += $file->getSize();
                }
            }
        } catch (\Throwable $e) {
            // Direktori tidak dapat diakses, return 0
            error_log("ServerMonitor: Cannot read dir {$dir}: " . $e->getMessage());
        }

        return $bytes / 1048576; // bytes → MB
    }

    /**
     * Mengambil detail network interfaces di Linux menggunakan ip -j
     */
    private function getNetworkInterfaces(bool $isLinux): array
    {
        if (!$isLinux) {
            // Mock data for development on Windows
            return [
                [
                    'interface' => 'eth0',
                    'mac'       => '00:15:5d:01:23:45',
                    'ipv4'      => '192.168.1.10',
                    'cidr'      => '24',
                    'gateway'   => '192.168.1.1',
                    'dhcp'      => true,
                    'dns'       => ['8.8.8.8', '8.8.4.4']
                ],
                [
                    'interface' => 'ens33',
                    'mac'       => '00:0c:29:ab:cd:ef',
                    'ipv4'      => '10.0.0.15',
                    'cidr'      => '16',
                    'gateway'   => '10.0.0.1',
                    'dhcp'      => false,
                    'dns'       => ['1.1.1.1']
                ]
            ];
        }

        $interfaces = [];
        $defaultGateways = [];

        // 1. Dapatkan default gateway
        $routeJson = shell_exec('timeout 1 ip -j route show default 2>/dev/null');
        if ($routeJson) {
            $routes = json_decode($routeJson, true);
            if (is_array($routes)) {
                foreach ($routes as $route) {
                    if (isset($route['dev']) && isset($route['gateway'])) {
                        $defaultGateways[$route['dev']] = $route['gateway'];
                    }
                }
            }
        }

        // 2. Baca active DNS
        $dns = [];
        if (is_readable('/etc/resolv.conf')) {
            $lines = file('/etc/resolv.conf', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (preg_match('/^nameserver\s+(.+)/', trim($line), $matches)) {
                    $ns = trim($matches[1]);
                    if ($ns !== '127.0.0.53') { // Abaikan local systemd-resolved stub
                        $dns[] = $ns;
                    }
                }
            }
        }
        if (empty($dns)) {
            $resolvectl = shell_exec('timeout 1 resolvectl dns 2>/dev/null');
            if ($resolvectl) {
                preg_match_all('/(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})/', $resolvectl, $matches);
                if (!empty($matches[0])) {
                    $dns = array_values(array_unique($matches[0]));
                }
            }
        }
        if (empty($dns)) {
            $dns = ['8.8.8.8', '8.8.4.4']; // Fallback
        }

        // 3. Baca rincian adapter jaringan
        $addrJson = shell_exec('timeout 1 ip -j addr show 2>/dev/null');
        if ($addrJson) {
            $addrList = json_decode($addrJson, true);
            if (is_array($addrList)) {
                foreach ($addrList as $item) {
                    $name = $item['ifname'] ?? '';
                    if ($name === 'lo' || ($item['link_type'] ?? '') === 'loopback') {
                        continue; // Lewati loopback
                    }

                    $mac = $item['address'] ?? 'N/A';
                    $ipv4 = '';
                    $cidr = '';
                    $dhcp = false;

                    if (isset($item['addr_info']) && is_array($item['addr_info'])) {
                        foreach ($item['addr_info'] as $info) {
                            if (($info['family'] ?? '') === 'inet') {
                                $ipv4 = $info['local'] ?? '';
                                $cidr = $info['prefixlen'] ?? '';
                                if (isset($info['dynamic']) && $info['dynamic'] === true) {
                                    $dhcp = true;
                                }
                                break;
                            }
                        }
                    }

                    // Fallback pengecekan flag dhcp dari route protocol
                    if (!$dhcp && $routeJson) {
                        $routes = json_decode($routeJson, true);
                        if (is_array($routes)) {
                            foreach ($routes as $route) {
                                if (isset($route['dev']) && $route['dev'] === $name) {
                                    if (isset($route['protocol']) && $route['protocol'] === 'dhcp') {
                                        $dhcp = true;
                                    }
                                }
                            }
                        }
                    }

                    $interfaces[] = [
                        'interface' => $name,
                        'mac'       => $mac,
                        'ipv4'      => $ipv4,
                        'cidr'      => (string)$cidr,
                        'gateway'   => $defaultGateways[$name] ?? '',
                        'dhcp'      => $dhcp,
                        'dns'       => $dns
                    ];
                }
            }
        }

        return $interfaces;
    }

    /**
     * POST /api/v1/super-admin/server-monitor/save-network
     * Menyimpan konfigurasi network baru (Netplan YAML) & menerapkan.
     */
    public function saveNetworkConfig(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Method not allowed'], 405);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $interface = trim($input['interface'] ?? '');
        $dhcp      = filter_var($input['dhcp'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $ipv4      = trim($input['ipv4'] ?? '');
        $gateway   = trim($input['gateway'] ?? '');
        $dns       = trim($input['dns'] ?? ''); 

        if (empty($interface)) {
            $this->jsonResponse(['error' => 'Nama interface tidak boleh kosong.'], 400);
            return;
        }

        if (!$dhcp) {
            if (empty($ipv4)) {
                $this->jsonResponse(['error' => 'IP Address / CIDR wajib diisi untuk konfigurasi Static.'], 400);
                return;
            }
            if (!preg_match('/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\/\d{1,2}$/', $ipv4)) {
                $this->jsonResponse(['error' => 'Format IP Address / CIDR tidak valid (contoh: 192.168.1.10/24).'], 400);
                return;
            }
            if (!empty($gateway) && !filter_var($gateway, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                $this->jsonResponse(['error' => 'Format Gateway tidak valid.'], 400);
                return;
            }
        }

        // 1. Generate Netplan YAML
        $yaml = "network:\n";
        $yaml .= "  version: 2\n";
        $yaml .= "  ethernets:\n";
        $yaml .= "    {$interface}:\n";
        if ($dhcp) {
            $yaml .= "      dhcp4: true\n";
        } else {
            $yaml .= "      dhcp4: false\n";
            $yaml .= "      addresses:\n";
            $yaml .= "        - {$ipv4}\n";
            if (!empty($gateway)) {
                $yaml .= "      routes:\n";
                $yaml .= "        - to: default\n";
                $yaml .= "          via: {$gateway}\n";
            }
            if (!empty($dns)) {
                $dnsList = array_map('trim', explode(',', $dns));
                $dnsList = array_filter($dnsList);
                $yaml .= "      nameservers:\n";
                $yaml .= "        addresses:\n";
                foreach ($dnsList as $d) {
                    $yaml .= "          - {$d}\n";
                }
            }
        }

        // 2. Simpan file netplan di /tmp/99-custom-network.yaml
        $tempDir = PHP_OS_FAMILY === 'Linux' ? '/tmp' : sys_get_temp_dir();
        $tempFile = $tempDir . DIRECTORY_SEPARATOR . '99-custom-network.yaml';

        if (file_put_contents($tempFile, $yaml) === false) {
            $this->jsonResponse(['error' => 'Gagal menulis file konfigurasi sementara.'], 500);
            return;
        }

        // 3. Terapkan konfigurasi jika di Linux
        if (PHP_OS_FAMILY === 'Linux') {
            $copyOutput = [];
            $copyStatus = 0;
            exec('sudo cp ' . escapeshellarg($tempFile) . ' /etc/netplan/99-custom-network.yaml 2>&1', $copyOutput, $copyStatus);

            if ($copyStatus !== 0) {
                $errorMsg = implode("\n", $copyOutput);
                $this->jsonResponse([
                    'error' => "Gagal menyalin konfigurasi ke netplan. Pastikan user web server (www-data) diizinkan menjalankan sudo tanpa password.\nDetail: " . $errorMsg
                ], 500);
                return;
            }

            $applyOutput = [];
            $applyStatus = 0;
            exec('sudo netplan apply 2>&1', $applyOutput, $applyStatus);

            if ($applyStatus !== 0) {
                $errorMsg = implode("\n", $applyOutput);
                $this->jsonResponse([
                    'error' => "Gagal menerapkan konfigurasi Netplan. Pastikan user web server (www-data) diizinkan menjalankan sudo tanpa password.\nDetail: " . $errorMsg
                ], 500);
                return;
            }

            $this->jsonResponse([
                'success' => true,
                'message' => 'Konfigurasi jaringan berhasil diperbarui dan diterapkan.'
            ]);
        } else {
            $this->jsonResponse([
                'success' => true,
                'message' => '[MOCK] Konfigurasi jaringan disimulasikan berhasil diperbarui di Windows. Berkas disimpan di: ' . $tempFile
            ]);
        }
    }
    /**
     * POST /api/v1/super-admin/server-monitor/update-server
     * Menjalankan script deploy.sh untuk update kode & migrasi
     */
    public function updateServer(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Method not allowed'], 405);
            return;
        }

        // Jalankan bash deploy.sh via shell_exec
        // Perhatian: Ini bergantung pada user web server (misal www-data) yang bisa menjalankan `sudo` tanpa password untuk script ini.
        $deployScript = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'deploy.sh';
        
        if (!file_exists($deployScript)) {
            $this->jsonResponse(['error' => 'Script deploy.sh tidak ditemukan di ' . escapeshellarg($deployScript)], 404);
            return;
        }

        // Eksekusi (kami tambahkan 2>&1 agar error stderr juga tertangkap)
        $output = shell_exec('bash ' . escapeshellarg($deployScript) . ' 2>&1');

        $this->jsonResponse([
            'success' => true,
            'message' => 'Proses update dijalankan.',
            'output' => $output ?: 'Tidak ada output dari script (atau eksekusi diblokir oleh server permissions).'
        ]);
    }
}
