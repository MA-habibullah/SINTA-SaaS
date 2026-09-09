<?php

namespace Modules\Sistem\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Modules\Core\Entities\Tenant;
use Modules\Sistem\Entities\ActiveSession;
use Modules\Sistem\Entities\ActivityLog;

class ActiveSessionController extends Controller
{
    /**
     * Tampilkan Halaman Utama Monitoring Sesi Aktif & Analitik Keamanan
     * GET /utilitas/sesi-aktif
     */
    public function index(Request $request): InertiaResponse|JsonResponse
    {
        $user = auth()->user();
        $userRoleName = is_object($user?->role) ? ($user->role->nama_role ?? 'admin_sekolah') : ($user?->role ?? 'admin_sekolah');
        $isSuperAdmin = ($user && ($userRoleName === 'super_admin' || $user->tenant_id === '00000000-0000-0000-0000-000000000000'));
        $currentTenantId = session('tenant_id') ?? $user?->tenant_id ?? '00000000-0000-0000-0000-000000000000';

        // 1. Ringkasan Statistik Real-time
        $statsWhere = [];
        $statsBindings = [];
        if (!$isSuperAdmin) {
            $statsWhere[] = "tenant_id = :tenant_id";
            $statsBindings['tenant_id'] = $currentTenantId;
        }

        $statsWhereClause = !empty($statsWhere) ? "WHERE " . implode(' AND ', $statsWhere) : "";

        // Total Sesi Aktif Hari Ini
        $totalSessionsToday = DB::selectOne("
            SELECT COUNT(*) AS total 
            FROM sistem.active_sessions 
            {$statsWhereClause} " . ($statsWhereClause ? "AND " : "WHERE ") . "last_activity >= CURRENT_DATE
        ", $statsBindings)->total ?? 0;

        // Total Pengguna Unik Hari Ini
        $uniqueUsersToday = DB::selectOne("
            SELECT COUNT(DISTINCT user_id) AS total 
            FROM sistem.active_sessions 
            {$statsWhereClause} " . ($statsWhereClause ? "AND " : "WHERE ") . "last_activity >= CURRENT_DATE
        ", $statsBindings)->total ?? 0;

        // Total Login 24 Jam Terakhir
        $auditWhereBindings = $statsBindings;
        $auditWhereClause = !empty($statsWhere) ? "WHERE al.tenant_id = :tenant_id AND " : "WHERE ";
        $totalLogins24h = DB::selectOne("
            SELECT COUNT(*) AS total 
            FROM sistem.activity_logs al
            {$auditWhereClause} al.action = 'LOGIN' AND al.created_at >= CURRENT_TIMESTAMP - INTERVAL '1 day'
        ", $auditWhereBindings)->total ?? 0;

        $totalLogouts24h = DB::selectOne("
            SELECT COUNT(*) AS total 
            FROM sistem.activity_logs al
            {$auditWhereClause} al.action = 'LOGOUT' AND al.created_at >= CURRENT_TIMESTAMP - INTERVAL '1 day'
        ", $auditWhereBindings)->total ?? 0;

        // Tenants List for Super Admin Switcher
        $tenantsList = [];
        if ($isSuperAdmin) {
            $tenantsList = DB::table('core.tenants')
                ->select('id', 'nama_sekolah', 'npsn', 'subdomain')
                ->where('id', '!=', '00000000-0000-0000-0000-000000000000')
                ->orderBy('nama_sekolah', 'asc')
                ->get();
        }

        if ($request->wantsJson() && !$request->header('X-Inertia')) {
            return response()->json([
                'success' => true,
                'stats' => [
                    'total_sessions_today' => (int)$totalSessionsToday,
                    'unique_users_today'   => (int)$uniqueUsersToday,
                    'total_logins_24h'     => (int)$totalLogins24h,
                    'total_logouts_24h'    => (int)$totalLogouts24h,
                ],
                'is_super_admin' => $isSuperAdmin,
                'tenants_list'   => $tenantsList,
            ]);
        }

        return Inertia::render('Sistem/ActiveSessions/Index', [
            'initialStats' => [
                'total_sessions_today' => (int)$totalSessionsToday,
                'unique_users_today'   => (int)$uniqueUsersToday,
                'total_logins_24h'     => (int)$totalLogins24h,
                'total_logouts_24h'    => (int)$totalLogouts24h,
            ],
            'isSuperAdmin' => $isSuperAdmin,
            'tenantsList'  => $tenantsList,
            'currentTenantId' => $currentTenantId,
        ]);
    }

    /**
     * API: Ambil data analitik sesi & chart tren
     * GET /utilitas/sesi-aktif/data
     */
    public function fetchData(Request $request): JsonResponse
    {
        $user = auth()->user();
        $userRoleName = is_object($user?->role) ? ($user->role->nama_role ?? 'admin_sekolah') : ($user?->role ?? 'admin_sekolah');
        $isSuperAdmin = ($user && ($userRoleName === 'super_admin' || $user->tenant_id === '00000000-0000-0000-0000-000000000000'));
        $currentTenantId = session('tenant_id') ?? $user?->tenant_id ?? '00000000-0000-0000-0000-000000000000';

        $timeframe = $request->input('timeframe', '30_days');
        $startDate = $request->input('start_date');
        $endDate   = $request->input('end_date');
        $targetTenantId = $request->input('tenant_id');

        try {
            // 1. QUERY: Tabel Riwayat Sesi Pengguna
            $onlineWhere = "1=1";
            $onlineParams = [];

            if (!empty($startDate) && !empty($endDate)) {
                $onlineWhere .= " AND s.last_activity::date BETWEEN :start_date::date AND :end_date::date";
                $onlineParams['start_date'] = $startDate;
                $onlineParams['end_date']   = $endDate;
            } else {
                $onlineWhere .= " AND s.last_activity >= CURRENT_TIMESTAMP - INTERVAL '30 days'";
            }

            if (!$isSuperAdmin) {
                $onlineWhere .= " AND s.tenant_id = :tenant_id";
                $onlineParams['tenant_id'] = $currentTenantId;
            } elseif ($targetTenantId && $targetTenantId !== '00000000-0000-0000-0000-000000000000') {
                $onlineWhere .= " AND s.tenant_id = :tenant_id";
                $onlineParams['tenant_id'] = $targetTenantId;
            }

            $sqlOnline = "
                SELECT 
                    s.id, 
                    s.ip_address, 
                    s.user_agent, 
                    s.last_activity,
                    s.tanggal_login,
                    COALESCE(u.nama_lengkap, sw.nama_lengkap, 'User') AS nama_lengkap,
                    COALESCE(r.nama_role, 'siswa') AS user_role,
                    t.nama_sekolah
                FROM sistem.active_sessions s
                LEFT JOIN core.users u ON s.user_id = u.id
                LEFT JOIN core.roles r ON u.role_id = r.id
                LEFT JOIN siswa.siswa sw ON s.user_id = sw.id
                LEFT JOIN core.tenants t ON s.tenant_id = t.id
                WHERE {$onlineWhere}
                ORDER BY s.last_activity DESC
                LIMIT 500
            ";

            $onlineUsers = DB::select($sqlOnline, $onlineParams);

            // 2. QUERY: Tren Sesi Pengguna Unik
            $chartParams = [];
            $groupBy = "s.tanggal_login::date";
            $selectGroup = "s.tanggal_login::date::text AS label";
            $chartWhere = "1=1";

            if ($timeframe === '30_minutes') {
                $chartWhere .= " AND s.last_activity >= CURRENT_TIMESTAMP - INTERVAL '30 minutes'";
                $groupBy = "to_char(s.last_activity, 'HH24:MI')";
                $selectGroup = "to_char(s.last_activity, 'HH24:MI') AS label";
            } elseif ($timeframe === '1_hour') {
                $chartWhere .= " AND s.last_activity >= CURRENT_TIMESTAMP - INTERVAL '1 hour'";
                $groupBy = "to_char(s.last_activity, 'HH24:MI')";
                $selectGroup = "to_char(s.last_activity, 'HH24:MI') AS label";
            } elseif ($timeframe === '1_day') {
                $chartWhere .= " AND s.last_activity >= CURRENT_TIMESTAMP - INTERVAL '1 day'";
                $groupBy = "to_char(s.last_activity, 'HH24:00')";
                $selectGroup = "to_char(s.last_activity, 'HH24:00') AS label";
            } elseif ($timeframe === '15_days') {
                $chartWhere .= " AND s.last_activity::date >= CURRENT_DATE - INTERVAL '15 days'";
            } else {
                $chartWhere .= " AND s.last_activity::date >= CURRENT_DATE - INTERVAL '30 days'";
            }

            if (!$isSuperAdmin) {
                $chartWhere .= " AND s.tenant_id = :tenant_id";
                $chartParams['tenant_id'] = $currentTenantId;
            } elseif ($targetTenantId && $targetTenantId !== '00000000-0000-0000-0000-000000000000') {
                $chartWhere .= " AND s.tenant_id = :tenant_id";
                $chartParams['tenant_id'] = $targetTenantId;
            }

            $sqlChart = "
                SELECT 
                    {$selectGroup}, 
                    COUNT(DISTINCT s.user_id) AS total_users
                FROM sistem.active_sessions s
                WHERE {$chartWhere}
                GROUP BY {$groupBy}
                ORDER BY MIN(s.last_activity) ASC
            ";

            $chartData = DB::select($sqlChart, $chartParams);

            // 3. QUERY: Tren Login & Logout dari sistem.activity_logs
            $auditParams = [];
            $auditGroupBy = "al.created_at::date";
            $auditSelectGroup = "al.created_at::date::text AS label";
            $auditWhere = "al.action IN ('LOGIN', 'LOGOUT', 'SYSTEM_TIMEOUT')";
            $auditOrderBy = "MIN(al.created_at)";

            if ($timeframe === '30_minutes') {
                $auditWhere .= " AND al.created_at >= CURRENT_TIMESTAMP - INTERVAL '30 minutes'";
                $auditGroupBy = "to_char(al.created_at, 'HH24:MI')";
                $auditSelectGroup = "to_char(al.created_at, 'HH24:MI') AS label";
            } elseif ($timeframe === '1_hour') {
                $auditWhere .= " AND al.created_at >= CURRENT_TIMESTAMP - INTERVAL '1 hour'";
                $auditGroupBy = "to_char(al.created_at, 'HH24:MI')";
                $auditSelectGroup = "to_char(al.created_at, 'HH24:MI') AS label";
            } elseif ($timeframe === '1_day') {
                $auditWhere .= " AND al.created_at >= CURRENT_TIMESTAMP - INTERVAL '1 day'";
                $auditGroupBy = "to_char(al.created_at, 'HH24:00')";
                $auditSelectGroup = "to_char(al.created_at, 'HH24:00') AS label";
            } elseif ($timeframe === '15_days') {
                $auditWhere .= " AND al.created_at::date >= CURRENT_DATE - INTERVAL '15 days'";
            } else {
                $auditWhere .= " AND al.created_at::date >= CURRENT_DATE - INTERVAL '30 days'";
            }

            if (!$isSuperAdmin) {
                $auditWhere .= " AND al.tenant_id = :tenant_id";
                $auditParams['tenant_id'] = $currentTenantId;
            } elseif ($targetTenantId && $targetTenantId !== '00000000-0000-0000-0000-000000000000') {
                $auditWhere .= " AND al.tenant_id = :tenant_id";
                $auditParams['tenant_id'] = $targetTenantId;
            }

            $sqlAuditChart = "
                SELECT 
                    {$auditSelectGroup}, 
                    SUM(CASE WHEN al.action = 'LOGIN' THEN 1 ELSE 0 END) AS total_logins,
                    SUM(CASE WHEN al.action = 'LOGOUT' THEN 1 ELSE 0 END) AS total_logouts
                FROM sistem.activity_logs al
                WHERE {$auditWhere}
                GROUP BY {$auditGroupBy}
                ORDER BY {$auditOrderBy} ASC
            ";

            $auditChartData = DB::select($sqlAuditChart, $auditParams);

            return response()->json([
                'success'          => true,
                'online_users'     => $onlineUsers,
                'chart_data'       => $chartData,
                'audit_chart_data' => $auditChartData,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error'   => 'Terjadi kesalahan sistem saat memuat data analitik sesi: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * API: Ambil log jejak audit login & logout
     * GET /utilitas/sesi-aktif/audit
     */
    public function fetchAudit(Request $request): JsonResponse
    {
        $user = auth()->user();
        $userRoleName = is_object($user?->role) ? ($user->role->nama_role ?? 'admin_sekolah') : ($user?->role ?? 'admin_sekolah');
        $isSuperAdmin = ($user && ($userRoleName === 'super_admin' || $user->tenant_id === '00000000-0000-0000-0000-000000000000'));
        $currentTenantId = session('tenant_id') ?? $user?->tenant_id ?? '00000000-0000-0000-0000-000000000000';

        $startDate = $request->input('start_date');
        $endDate   = $request->input('end_date');
        $targetTenantId = $request->input('tenant_id');

        try {
            $where = "al.action IN ('LOGIN', 'LOGOUT', 'SYSTEM_TIMEOUT')";
            $params = [];

            if (!$isSuperAdmin) {
                $where .= " AND al.tenant_id = :tenant_id";
                $params['tenant_id'] = $currentTenantId;
            } elseif ($targetTenantId && $targetTenantId !== '00000000-0000-0000-0000-000000000000') {
                $where .= " AND al.tenant_id = :tenant_id";
                $params['tenant_id'] = $targetTenantId;
            }

            if (!empty($startDate) && !empty($endDate)) {
                $where .= " AND al.created_at::date BETWEEN :start_date::date AND :end_date::date";
                $params['start_date'] = $startDate;
                $params['end_date']   = $endDate;
            }

            $sql = "
                SELECT 
                    al.id,
                    al.action,
                    al.created_at,
                    al.ip_address,
                    al.user_role,
                    COALESCE(u.nama_lengkap, sw.nama_lengkap, 'System/Unknown') AS nama_lengkap,
                    t.nama_sekolah
                FROM sistem.activity_logs al
                LEFT JOIN core.users u ON al.user_id = u.id
                LEFT JOIN siswa.siswa sw ON al.user_id = sw.id
                LEFT JOIN core.tenants t ON al.tenant_id = t.id
                WHERE {$where}
                ORDER BY al.created_at DESC
                LIMIT 500
            ";

            $logs = DB::select($sql, $params);

            return response()->json([
                'success'    => true,
                'audit_logs' => $logs,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error'   => 'Gagal memuat log keamanan: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * API: Hapus riwayat log sesi lama (Data Retention)
     * POST /utilitas/sesi-aktif/retention
     */
    public function deleteRetention(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date_limit' => 'required|date_format:Y-m-d',
        ]);

        $dateLimit = $validated['date_limit'];
        $user = auth()->user();
        $userRoleName = is_object($user?->role) ? ($user->role->nama_role ?? 'admin_sekolah') : ($user?->role ?? 'admin_sekolah');
        $isSuperAdmin = ($user && ($userRoleName === 'super_admin' || $user->tenant_id === '00000000-0000-0000-0000-000000000000'));
        $currentTenantId = session('tenant_id') ?? $user?->tenant_id ?? '00000000-0000-0000-0000-000000000000';

        try {
            $query = DB::table('sistem.active_sessions')->whereDate('last_activity', '<=', $dateLimit);

            if (!$isSuperAdmin) {
                $query->where('tenant_id', $currentTenantId);
            }

            $affectedCount = $query->delete();

            return response()->json([
                'success' => true,
                'message' => "Berhasil membersihkan {$affectedCount} log riwayat sesi sebelum atau pada tanggal {$dateLimit}.",
                'affected_count' => $affectedCount,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error'   => 'Gagal membersihkan log retensi: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * API: Hapus log audit keamanan (Data Retention)
     * POST /utilitas/sesi-aktif/audit/retention
     */
    public function deleteAuditRetention(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date_limit' => 'required|date_format:Y-m-d',
        ]);

        $dateLimit = $validated['date_limit'];
        $user = auth()->user();
        $userRoleName = is_object($user?->role) ? ($user->role->nama_role ?? 'admin_sekolah') : ($user?->role ?? 'admin_sekolah');
        $isSuperAdmin = ($user && ($userRoleName === 'super_admin' || $user->tenant_id === '00000000-0000-0000-0000-000000000000'));
        $currentTenantId = session('tenant_id') ?? $user?->tenant_id ?? '00000000-0000-0000-0000-000000000000';

        try {
            $query = DB::table('sistem.activity_logs')
                ->whereIn('action', ['LOGIN', 'LOGOUT', 'SYSTEM_TIMEOUT'])
                ->whereDate('created_at', '<=', $dateLimit);

            if (!$isSuperAdmin) {
                $query->where('tenant_id', $currentTenantId);
            }

            $affectedCount = $query->delete();

            return response()->json([
                'success' => true,
                'message' => "Berhasil membersihkan {$affectedCount} log jejak keamanan (Login/Logout) sebelum atau pada tanggal {$dateLimit}.",
                'affected_count' => $affectedCount,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error'   => 'Gagal membersihkan log audit: ' . $e->getMessage(),
            ], 500);
        }
    }
}
