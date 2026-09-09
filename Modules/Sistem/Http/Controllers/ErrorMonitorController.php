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
use Modules\Sistem\Entities\SystemError;
use Modules\Core\Entities\Tenant;

class ErrorMonitorController extends Controller
{
    /**
     * Tampilkan Halaman Utama Error Monitor
     * GET /super-admin/error-monitor
     * GET /utilitas/error-monitor
     */
    public function index(Request $request): InertiaResponse|JsonResponse
    {
        $user = Auth::user();
        [$isSuperAdmin, $userTenantId, $userRole] = $this->resolveUserAuth($user);

        if (!$isSuperAdmin) {
            abort(403, 'Akses ditolak. Halaman Error Monitor hanya dapat diakses oleh Super Admin Platform.');
        }

        // Ambil Daftar Sekolah (Tenant)
        $tenants = Tenant::select('id', 'nama_sekolah', 'npsn')
            ->orderBy('nama_sekolah', 'asc')
            ->get();

        // Ambil Daftar Error Levels Unik
        $levels = DB::table('sistem.system_errors')
            ->select('error_level', DB::raw('count(*) as count'))
            ->whereNotNull('error_level')
            ->groupBy('error_level')
            ->orderBy('count', 'desc')
            ->get();

        // Ambil Statistik Ringkasan Metrik
        $stats = $this->calculateErrorStats();

        // Query Error Logs Awal
        $perPage = (int)$request->input('per_page', 20);
        if ($perPage < 5 || $perPage > 100) $perPage = 20;

        $query = $this->buildErrorsQuery($request);
        $errors = $query->paginate($perPage)->withQueryString();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $errors,
                'stats' => $stats,
            ]);
        }

        return Inertia::render('Sistem/ErrorMonitor/Index', [
            'errors' => $errors,
            'stats' => $stats,
            'tenantsList' => $tenants,
            'levelsList' => $levels,
            'filters' => [
                'search' => $request->input('search', ''),
                'level_filter' => $request->input('level_filter', ''),
                'tenant_filter' => $request->input('tenant_filter', ''),
                'start_date' => $request->input('start_date', ''),
                'end_date' => $request->input('end_date', ''),
            ],
            'isSuperAdmin' => true,
        ]);
    }

    /**
     * API: Ambil data error terpaginasi & tersaring
     * GET /utilitas/error-monitor/data
     */
    public function fetchData(Request $request): JsonResponse
    {
        $user = Auth::user();
        [$isSuperAdmin] = $this->resolveUserAuth($user);

        if (!$isSuperAdmin) {
            return response()->json(['error' => 'Akses ditolak.'], 403);
        }

        $perPage = (int)$request->input('per_page', 20);
        if ($perPage < 5 || $perPage > 100) $perPage = 20;

        $query = $this->buildErrorsQuery($request);
        $errors = $query->paginate($perPage);

        $stats = $this->calculateErrorStats();

        return response()->json([
            'success' => true,
            'data' => $errors,
            'stats' => $stats,
        ]);
    }

    /**
     * API: Hapus satu error log berdasarkan ID
     * POST /utilitas/error-monitor/delete
     */
    public function deleteOne(Request $request): JsonResponse
    {
        $user = Auth::user();
        [$isSuperAdmin] = $this->resolveUserAuth($user);

        if (!$isSuperAdmin) {
            return response()->json(['error' => 'Akses ditolak.'], 403);
        }

        $id = $request->input('id');
        if (empty($id)) {
            return response()->json(['error' => 'ID error tidak boleh kosong.'], 422);
        }

        try {
            $deleted = DB::table('sistem.system_errors')->where('id', $id)->delete();
            return response()->json([
                'success' => true,
                'message' => 'Log error berhasil dihapus.',
                'deleted' => $deleted,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Gagal menghapus log error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * API: Bersihkan semua log error / retensi log
     * POST /utilitas/error-monitor/clear
     */
    public function clearAll(Request $request): JsonResponse
    {
        $user = Auth::user();
        [$isSuperAdmin] = $this->resolveUserAuth($user);

        if (!$isSuperAdmin) {
            return response()->json(['error' => 'Akses ditolak.'], 403);
        }

        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $tenantId = $request->input('tenant_id');

        try {
            $query = DB::table('sistem.system_errors');

            if (!empty($startDate) && !empty($endDate)) {
                $query->whereBetween('created_at', [
                    Carbon::parse($startDate)->startOfDay(),
                    Carbon::parse($endDate)->endOfDay(),
                ]);
            }

            if (!empty($tenantId) && $tenantId !== 'all') {
                if ($tenantId === 'system') {
                    $query->whereNull('tenant_id');
                } else {
                    $query->where('tenant_id', $tenantId);
                }
            }

            $count = $query->delete();

            return response()->json([
                'success' => true,
                'message' => "Berhasil membersihkan {$count} log error sistem.",
                'deleted_count' => $count,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Gagal membersihkan log: ' . $e->getMessage()], 500);
        }
    }

    /**
     * API: Merekam error dari frontend (Global Client Error Tracker)
     * POST /api/v1/error-monitor/log-client
     */
    public function logClientError(Request $request): JsonResponse
    {
        $message = trim((string)$request->input('message', ''));
        if (empty($message)) {
            return response()->json(['error' => 'Pesan error wajib diisi.'], 422);
        }

        try {
            $user = Auth::user();
            $tenantId = $user ? $user->tenant_id : $request->input('tenant_id');

            DB::table('sistem.system_errors')->insert([
                'id' => (string)Str::uuid(),
                'tenant_id' => !empty($tenantId) && $tenantId !== '00000000-0000-0000-0000-000000000000' ? $tenantId : null,
                'error_level' => $request->input('error_level', 'JS_ERROR'),
                'message' => Str::limit($message, 5000),
                'file' => Str::limit($request->input('file', 'Client Browser'), 500),
                'line' => (int)$request->input('line', 0),
                'trace' => json_encode($request->input('trace', [])),
                'request_url' => Str::limit($request->input('request_url', $request->fullUrl()), 1000),
                'request_method' => $request->method(),
                'user_agent' => $request->userAgent(),
                'ip_address' => $request->ip(),
                'context' => json_encode([
                    'user_id' => $user ? $user->id : null,
                    'user_email' => $user ? $user->email : null,
                    'client_timestamp' => Carbon::now()->toIso8601String(),
                ]),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);

            return response()->json(['success' => true, 'message' => 'Error client berhasil direkam.']);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Gagal mencatat log client: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Helper: Kueri penyaringan error
     */
    private function buildErrorsQuery(Request $request)
    {
        $query = DB::table('sistem.system_errors as e')
            ->leftJoin('core.tenants as t', 'e.tenant_id', '=', 't.id')
            ->select([
                'e.id',
                'e.tenant_id',
                'e.error_level',
                'e.message',
                'e.file',
                'e.line',
                'e.trace',
                'e.request_url',
                'e.request_method',
                'e.user_agent',
                'e.ip_address',
                'e.context',
                'e.created_at',
                't.nama_sekolah',
                't.subdomain',
            ])
            ->orderBy('e.created_at', 'desc');

        // Filter Level
        if ($request->filled('level_filter')) {
            $query->where('e.error_level', $request->input('level_filter'));
        }

        // Filter Tenant
        if ($request->filled('tenant_filter')) {
            $tenant = $request->input('tenant_filter');
            if ($tenant === 'system') {
                $query->whereNull('e.tenant_id');
            } elseif ($tenant !== 'all') {
                $query->where('e.tenant_id', $tenant);
            }
        }

        // Filter Tanggal
        if ($request->filled('start_date')) {
            $query->whereDate('e.created_at', '>=', $request->input('start_date'));
        }
        if ($request->filled('end_date')) {
            $query->whereDate('e.created_at', '<=', $request->input('end_date'));
        }

        // Search Query
        if ($request->filled('search')) {
            $search = '%' . trim($request->input('search')) . '%';
            $query->where(function ($q) use ($search) {
                $q->where('e.message', 'ILIKE', $search)
                  ->orWhere('e.file', 'ILIKE', $search)
                  ->orWhere('e.request_url', 'ILIKE', $search)
                  ->orWhere('e.error_level', 'ILIKE', $search)
                  ->orWhere('e.ip_address', 'ILIKE', $search)
                  ->orWhere('t.nama_sekolah', 'ILIKE', $search)
                  ->orWhereRaw("CAST(e.trace AS text) ILIKE ?", [$search])
                  ->orWhereRaw("CAST(e.context AS text) ILIKE ?", [$search]);
            });
        }

        return $query;
    }

    /**
     * Helper: Hitung metrik KPI error sistem
     */
    private function calculateErrorStats(): array
    {
        $totalAll = DB::table('sistem.system_errors')->count();
        $totalToday = DB::table('sistem.system_errors')->whereDate('created_at', Carbon::today())->count();
        
        $criticalCount = DB::table('sistem.system_errors')
            ->whereIn('error_level', ['CRITICAL', 'FATAL', 'ParseError', 'PDOException', 'Error', 'TypeError'])
            ->count();

        $warningCount = DB::table('sistem.system_errors')
            ->whereIn('error_level', ['E_WARNING', 'WARNING', 'E_NOTICE', 'NOTICE', 'E_DEPRECATED', 'DEPRECATED'])
            ->count();

        $clientCount = DB::table('sistem.system_errors')
            ->whereIn('error_level', ['JS_ERROR', 'JS Error', 'JS Promise Rejection', 'PROMISE_ERROR', 'CLIENT_ERROR'])
            ->count();

        return [
            'total_all' => $totalAll,
            'total_today' => $totalToday,
            'critical_count' => $criticalCount,
            'warning_count' => $warningCount,
            'client_count' => $clientCount,
        ];
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
