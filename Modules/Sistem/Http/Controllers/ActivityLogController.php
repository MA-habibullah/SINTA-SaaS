<?php

namespace Modules\Sistem\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Modules\Sistem\Entities\ActivityLog;
use Modules\Core\Entities\Tenant;

class ActivityLogController extends Controller
{
    /**
     * Tampilkan Halaman Utama Log Aktivitas (Audit Trail)
     * GET /utilitas/log-aktivitas
     */
    public function index(Request $request): InertiaResponse|JsonResponse
    {
        $user = Auth::user();
        [$isSuperAdmin, $userTenantId, $userRole] = $this->resolveUserAuth($user);

        // Ambil Daftar Sekolah (Tenant) untuk Super Admin
        $tenants = [];
        if ($isSuperAdmin) {
            $tenants = Tenant::select('id', 'nama_sekolah', 'npsn')
                ->orderBy('nama_sekolah', 'asc')
                ->get();
        }

        // Ambil Daftar Role Unik
        $rolesQuery = DB::table('sistem.activity_logs')->distinct()->select('user_role')->whereNotNull('user_role');
        if (!$isSuperAdmin && $userTenantId) {
            $rolesQuery->where('tenant_id', $userTenantId);
        }
        $roles = $rolesQuery->orderBy('user_role', 'asc')->pluck('user_role')->toArray();

        // Ambil Daftar Actions Unik
        $actions = ['INSERT', 'UPDATE', 'DELETE', 'LOGIN', 'LOGOUT', 'EXPORT', 'PRINT'];

        // Ambil Statistik KPI Hari Ini
        $today = Carbon::today();
        $statsQuery = DB::table('sistem.activity_logs')->whereDate('created_at', $today);
        if (!$isSuperAdmin && $userTenantId) {
            $statsQuery->where('tenant_id', $userTenantId);
        }
        $statsToday = [
            'total_today' => (clone $statsQuery)->count(),
            'total_insert' => (clone $statsQuery)->where('action', 'INSERT')->count(),
            'total_update' => (clone $statsQuery)->where('action', 'UPDATE')->count(),
            'total_delete' => (clone $statsQuery)->where('action', 'DELETE')->count(),
        ];

        // Query Logs Awal
        $perPage = (int)$request->input('per_page', 20);
        if ($perPage < 5 || $perPage > 100) $perPage = 20;

        $query = $this->buildLogsQuery($request, $isSuperAdmin, $userTenantId);
        $logs = $query->paginate($perPage)->withQueryString();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $logs,
                'stats' => $statsToday,
            ]);
        }

        return Inertia::render('Sistem/ActivityLogs/Index', [
            'logs' => $logs,
            'stats' => $statsToday,
            'tenantsList' => $tenants,
            'rolesList' => $roles,
            'actionsList' => $actions,
            'filters' => [
                'search' => $request->input('search', ''),
                'tenant_filter' => $request->input('tenant_filter', ''),
                'role_filter' => $request->input('role_filter', ''),
                'action_filter' => $request->input('action_filter', ''),
                'start_date' => $request->input('start_date', ''),
                'end_date' => $request->input('end_date', ''),
            ],
            'isSuperAdmin' => $isSuperAdmin,
        ]);
    }

    /**
     * API: Ambil data log aktivitas terpaginasi & tersaring
     * GET /utilitas/log-aktivitas/data
     */
    public function fetchData(Request $request): JsonResponse
    {
        $user = Auth::user();
        [$isSuperAdmin, $userTenantId, $userRole] = $this->resolveUserAuth($user);

        $perPage = (int)$request->input('per_page', 20);
        if ($perPage < 5 || $perPage > 100) $perPage = 20;

        $query = $this->buildLogsQuery($request, $isSuperAdmin, $userTenantId);
        $logs = $query->paginate($perPage);

        // Ambil Statistik KPI Terkini
        $today = Carbon::today();
        $statsQuery = DB::table('sistem.activity_logs')->whereDate('created_at', $today);
        if (!$isSuperAdmin && $userTenantId) {
            $statsQuery->where('tenant_id', $userTenantId);
        }
        $statsToday = [
            'total_today' => (clone $statsQuery)->count(),
            'total_insert' => (clone $statsQuery)->where('action', 'INSERT')->count(),
            'total_update' => (clone $statsQuery)->where('action', 'UPDATE')->count(),
            'total_delete' => (clone $statsQuery)->where('action', 'DELETE')->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $logs,
            'stats' => $statsToday,
        ]);
    }

    /**
     * API: Hapus log aktivitas berdasarkan rentang tanggal & filter tenant (Retention)
     * POST /utilitas/log-aktivitas/delete
     */
    public function deleteLogs(Request $request): JsonResponse
    {
        $user = Auth::user();
        [$isSuperAdmin, $userTenantId, $userRole] = $this->resolveUserAuth($user);

        if (!$isSuperAdmin && (!in_array($userRole, ['admin_sekolah', 'operator_sekolah', 'admin']))) {
            return response()->json(['error' => 'Akses ditolak. Anda tidak memiliki wewenang untuk membersihkan log.'], 403);
        }

        $validated = $request->validate([
            'startDate' => 'required|date',
            'endDate' => 'required|date|after_or_equal:startDate',
            'tenantId' => 'nullable|string',
        ]);

        $startDate = Carbon::parse($validated['startDate'])->startOfDay();
        $endDate = Carbon::parse($validated['endDate'])->endOfDay();
        $targetTenant = $validated['tenantId'] ?? '';

        if (!$isSuperAdmin) {
            $targetTenant = $userTenantId;
        }

        try {
            $deleteQuery = DB::table('sistem.activity_logs')
                ->whereBetween('created_at', [$startDate, $endDate]);

            if ($isSuperAdmin) {
                if ($targetTenant === 'system') {
                    $deleteQuery->whereNull('tenant_id');
                } elseif ($targetTenant !== 'all' && !empty($targetTenant)) {
                    $deleteQuery->where('tenant_id', $targetTenant);
                }
            } else {
                $deleteQuery->where('tenant_id', $userTenantId);
            }

            $deletedRows = $deleteQuery->delete();

            // Catat log penghapusan ke activity logs
            try {
                DB::table('sistem.activity_logs')->insert([
                    'id' => (string)Str::uuid(),
                    'tenant_id' => $userTenantId,
                    'user_id' => $user->id ?? null,
                    'user_role' => $user->role ?? 'system',
                    'table_name' => 'sistem.activity_logs',
                    'action' => 'DELETE',
                    'old_data' => null,
                    'new_data' => json_encode([
                        'deleted_rows' => $deletedRows,
                        'start_date' => $validated['startDate'],
                        'end_date' => $validated['endDate'],
                        'target' => $targetTenant,
                    ]),
                    'ip_address' => $request->ip(),
                    'created_at' => Carbon::now(),
                ]);
            } catch (\Throwable $ex) {
                // Ignore secondary log error
            }

            return response()->json([
                'success' => true,
                'message' => "Berhasil membersihkan {$deletedRows} baris log aktivitas sistem.",
                'deleted_count' => $deletedRows,
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => 'Gagal menghapus log aktivitas: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Helper kueri penyaringan log
     */
    private function buildLogsQuery(Request $request, bool $isSuperAdmin, ?string $userTenantId)
    {
        $query = DB::table('sistem.activity_logs as l')
            ->leftJoin('core.users as u', 'l.user_id', '=', 'u.id')
            ->leftJoin('core.tenants as t', 'l.tenant_id', '=', 't.id')
            ->select([
                'l.id',
                'l.tenant_id',
                'l.user_id',
                'l.user_role',
                'l.table_name',
                'l.action',
                'l.old_data',
                'l.new_data',
                'l.ip_address',
                'l.created_at',
                'u.nama_lengkap as actor_name',
                'u.email as actor_email',
                't.nama_sekolah',
                't.subdomain',
            ])
            ->orderBy('l.created_at', 'desc');

        // Multi-Tenant Isolation
        if ($isSuperAdmin) {
            $tenantFilter = $request->input('tenant_filter');
            if ($tenantFilter === 'system') {
                $query->whereNull('l.tenant_id');
            } elseif (!empty($tenantFilter) && $tenantFilter !== 'all') {
                $query->where('l.tenant_id', $tenantFilter);
            }
        } else {
            $query->where('l.tenant_id', $userTenantId);
            $query->where('l.user_role', '!=', 'super_admin');
        }

        // Role Filter
        if ($request->filled('role_filter')) {
            $query->where('l.user_role', $request->input('role_filter'));
        }

        // Action Filter
        if ($request->filled('action_filter')) {
            $query->where('l.action', $request->input('action_filter'));
        }

        // Date Range Filter
        if ($request->filled('start_date')) {
            $query->whereDate('l.created_at', '>=', $request->input('start_date'));
        }
        if ($request->filled('end_date')) {
            $query->whereDate('l.created_at', '<=', $request->input('end_date'));
        }

        // Search Query
        if ($request->filled('search')) {
            $search = '%' . trim($request->input('search')) . '%';
            $query->where(function ($q) use ($search) {
                $q->where('l.action', 'ILIKE', $search)
                  ->orWhere('l.table_name', 'ILIKE', $search)
                  ->orWhere('l.user_role', 'ILIKE', $search)
                  ->orWhere('u.nama_lengkap', 'ILIKE', $search)
                  ->orWhere('u.email', 'ILIKE', $search)
                  ->orWhere('l.ip_address', 'ILIKE', $search)
                  ->orWhere('t.nama_sekolah', 'ILIKE', $search)
                  ->orWhereRaw("CAST(l.new_data AS text) ILIKE ?", [$search])
                  ->orWhereRaw("CAST(l.old_data AS text) ILIKE ?", [$search]);
            });
        }

        return $query;
    }

    /**
     * Helper identifikasi otoritas & role pengguna
     */
    private function resolveUserAuth($user): array
    {
        if (!$user) {
            return [false, null, ''];
        }

        $isSuperAdmin = false;
        if (method_exists($user, 'isSuperAdmin')) {
            $isSuperAdmin = $user->isSuperAdmin();
        } else {
            $isSuperAdmin = !empty($user->is_super_admin);
        }

        $roleName = '';
        if (is_object($user->role)) {
            $roleName = $user->role->nama_role ?? '';
        } elseif (is_string($user->role)) {
            $roleName = $user->role;
        }

        if ($roleName === 'super_admin' || $user->tenant_id === '00000000-0000-0000-0000-000000000000') {
            $isSuperAdmin = true;
        }

        return [$isSuperAdmin, $user->tenant_id, $roleName];
    }
}

