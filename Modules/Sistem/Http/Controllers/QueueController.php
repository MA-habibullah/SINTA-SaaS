<?php

namespace Modules\Sistem\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Modules\Core\Entities\Tenant;
use Modules\Core\Entities\User;
use Modules\Sistem\Entities\QueueJob;

class QueueController extends Controller
{
    /**
     * Tampilkan Halaman Utama Monitoring Antrean Sistem
     * GET /utilitas/antrean
     */
    public function index(Request $request): InertiaResponse|JsonResponse
    {
        $user = Auth::user();
        $isSuperAdmin = $this->checkIsSuperAdmin($user);
        $roleName = $user && $user->role ? $user->role->nama_role : ($isSuperAdmin ? 'super_admin' : 'user');
        $tenantId = $isSuperAdmin ? null : ($user ? $user->tenant_id : null);

        // Ambil list tenant untuk Super Admin
        $tenantsList = [];
        if ($isSuperAdmin) {
            $tenantsList = Tenant::select('id', 'nama_sekolah', 'npsn')
                ->orderBy('nama_sekolah', 'asc')
                ->get();
        }

        // Ambil metrik awal
        $metrics = $this->calculateMetrics($isSuperAdmin, $tenantId);

        if ($request->wantsJson()) {
            return response()->json([
                'success'     => true,
                'metrics'     => $metrics,
                'tenantsList' => $tenantsList,
            ]);
        }

        return Inertia::render('Sistem/Queue/Index', [
            'metrics'      => $metrics,
            'tenantsList'  => $tenantsList,
            'isSuperAdmin' => $isSuperAdmin,
            'userRole'     => $roleName,
        ]);
    }

    /**
     * API: Ambil metrik ringkasan status dan daftar jobs terpaginasi
     * GET /utilitas/antrean/data
     */
    public function fetchData(Request $request): JsonResponse
    {
        $user = Auth::user();
        $isSuperAdmin = $this->checkIsSuperAdmin($user);
        $sessionTenantId = $user ? $user->tenant_id : null;

        $filterStatus = trim((string)$request->input('status', ''));
        $filterJobType = trim((string)$request->input('job_type', ''));
        $filterTenantId = trim((string)$request->input('tenant_id', ''));
        $perPage = max(5, min(100, (int)$request->input('per_page', 15)));
        $page = max(1, (int)$request->input('page', 1));

        // Tentukan tenant scope
        $effectiveTenantId = $isSuperAdmin ? $filterTenantId : $sessionTenantId;

        // 1. Hitung Metrics
        $metrics = $this->calculateMetrics($isSuperAdmin, $effectiveTenantId);

        // 2. Query Jobs
        $query = QueueJob::withoutTenant()
            ->with(['tenant:id,nama_sekolah,npsn'])
            ->orderBy('created_at', 'desc');

        if (!empty($effectiveTenantId)) {
            $query->where('tenant_id', $effectiveTenantId);
        }

        if (!empty($filterStatus)) {
            $query->where('status', $filterStatus);
        }

        if (!empty($filterJobType)) {
            $query->where('job_type', 'ILIKE', "%{$filterJobType}%");
        }

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        $jobs = collect($paginator->items())->map(function ($job) {
            return [
                'id'            => $job->id,
                'tenant_id'     => $job->tenant_id,
                'nama_sekolah'  => $job->tenant ? $job->tenant->nama_sekolah : 'Sistem / Global',
                'queue'         => $job->queue,
                'job_type'      => $job->job_type,
                'payload'       => is_array($job->payload) ? $job->payload : (json_decode($job->payload ?? '{}', true) ?: []),
                'attempts'      => (int)$job->attempts,
                'status'        => $job->status,
                'error_message' => $job->error_message,
                'available_at'  => $job->available_at ? $job->available_at->format('Y-m-d H:i:s') : null,
                'reserved_at'   => $job->reserved_at ? $job->reserved_at->format('Y-m-d H:i:s') : null,
                'completed_at'  => $job->completed_at ? $job->completed_at->format('Y-m-d H:i:s') : null,
                'created_at'    => $job->created_at ? $job->created_at->format('Y-m-d H:i:s') : null,
            ];
        });

        return response()->json([
            'success'      => true,
            'metrics'      => $metrics,
            'jobs'         => $jobs,
            'current_page' => $paginator->currentPage(),
            'total_pages'  => $paginator->lastPage(),
            'total_count'  => $paginator->total(),
        ]);
    }

    /**
     * API: Dispatch pekerjaan simulasi/baru ke antrean
     * POST /utilitas/antrean/dispatch
     */
    public function dispatchJob(Request $request): JsonResponse
    {
        $user = Auth::user();
        $isSuperAdmin = $this->checkIsSuperAdmin($user);
        $sessionTenantId = $user ? $user->tenant_id : null;

        $validated = $request->validate([
            'job_type'  => 'required|string|max:100',
            'payload'   => 'nullable|array',
            'tenant_id' => 'nullable|uuid',
            'queue'     => 'nullable|string|max:100',
        ]);

        $jobType = $validated['job_type'];
        $payload = $validated['payload'] ?? [];
        $queue = $validated['queue'] ?? 'default';

        $targetTenantId = $sessionTenantId;
        if ($isSuperAdmin && !empty($validated['tenant_id'])) {
            $targetTenantId = $validated['tenant_id'];
        }

        try {
            $job = QueueJob::withoutTenant()->create([
                'id'            => Str::uuid()->toString(),
                'tenant_id'     => $targetTenantId,
                'queue'         => $queue,
                'job_type'      => $jobType,
                'payload'       => $payload,
                'attempts'      => 0,
                'status'        => 'pending',
                'error_message' => null,
                'available_at'  => now(),
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => "Pekerjaan '{$jobType}' berhasil ditambahkan ke antrean sistem.",
                'data'    => $job,
            ], 201);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error'   => 'Gagal memasukkan pekerjaan ke antrean: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * API: Proses ulang pekerjaan yang gagal (Retry)
     * POST /utilitas/antrean/retry
     */
    public function retryJob(Request $request): JsonResponse
    {
        $user = Auth::user();
        $isSuperAdmin = $this->checkIsSuperAdmin($user);
        $sessionTenantId = $user ? $user->tenant_id : null;

        $jobId = $request->input('id');
        if (!$jobId) {
            return response()->json(['success' => false, 'error' => 'ID Pekerjaan tidak valid.'], 400);
        }

        $query = QueueJob::withoutTenant()->where('id', $jobId);
        if (!$isSuperAdmin && !empty($sessionTenantId)) {
            $query->where('tenant_id', $sessionTenantId);
        }

        $job = $query->first();
        if (!$job) {
            return response()->json(['success' => false, 'error' => 'Pekerjaan tidak ditemukan atau akses ditolak.'], 404);
        }

        try {
            $job->update([
                'status'        => 'pending',
                'attempts'      => 0,
                'error_message' => null,
                'reserved_at'   => null,
                'completed_at'  => null,
                'updated_at'    => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => "Pekerjaan #{$job->id} sukses diatur kembali ke status pending.",
                'data'    => $job,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error'   => 'Gagal mengatur ulang pekerjaan: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * API: Hapus pekerjaan dari antrean
     * POST /utilitas/antrean/delete
     */
    public function deleteJob(Request $request): JsonResponse
    {
        $user = Auth::user();
        $isSuperAdmin = $this->checkIsSuperAdmin($user);
        $sessionTenantId = $user ? $user->tenant_id : null;

        $jobId = $request->input('id');
        if (!$jobId) {
            return response()->json(['success' => false, 'error' => 'ID Pekerjaan tidak valid.'], 400);
        }

        $query = QueueJob::withoutTenant()->where('id', $jobId);
        if (!$isSuperAdmin && !empty($sessionTenantId)) {
            $query->where('tenant_id', $sessionTenantId);
        }

        $job = $query->first();
        if (!$job) {
            return response()->json(['success' => false, 'error' => 'Pekerjaan tidak ditemukan atau akses ditolak.'], 404);
        }

        try {
            $job->delete();

            return response()->json([
                'success' => true,
                'message' => "Pekerjaan #{$jobId} berhasil dihapus dari antrean.",
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error'   => 'Gagal menghapus pekerjaan: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * API: Jalankan satu pekerjaan terdepan via browser (Web Runner Execution)
     * POST /utilitas/antrean/run-worker
     */
    public function runWorker(Request $request): JsonResponse
    {
        $user = Auth::user();
        $isSuperAdmin = $this->checkIsSuperAdmin($user);
        $sessionTenantId = $user ? $user->tenant_id : null;

        // Ambil pekerjaan pending tertua
        $query = QueueJob::withoutTenant()
            ->where('status', 'pending')
            ->orderBy('created_at', 'asc');

        if (!$isSuperAdmin && !empty($sessionTenantId)) {
            $query->where('tenant_id', $sessionTenantId);
        }

        $job = $query->first();

        if (!$job) {
            return response()->json([
                'success' => false,
                'message' => 'Antrean kosong. Tidak ada tugas pending yang dapat diproses.',
            ]);
        }

        // Tandai processing
        $job->update([
            'status'      => 'processing',
            'attempts'    => ($job->attempts ?? 0) + 1,
            'reserved_at' => now(),
            'updated_at'  => now(),
        ]);

        $jobId = $job->id;
        $jobType = $job->job_type;
        $payload = is_array($job->payload) ? $job->payload : (json_decode($job->payload ?? '{}', true) ?: []);

        try {
            // Eksekusi logic berdasarkan job_type
            $this->executeJobLogic($jobType, $payload);

            // Tandai selesai
            $job->update([
                'status'       => 'completed',
                'completed_at' => now(),
                'error_message'=> null,
                'updated_at'   => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => "Pekerjaan #{$jobId} ({$jobType}) sukses diselesaikan di latar belakang.",
                'job_id'  => $jobId,
            ]);
        } catch (\Throwable $e) {
            // Tandai gagal
            $job->update([
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
                'updated_at'    => now(),
            ]);

            return response()->json([
                'success' => false,
                'error'   => "Pekerjaan #{$jobId} ({$jobType}) gagal diproses: " . $e->getMessage(),
                'job_id'  => $jobId,
            ]);
        }
    }

    /**
     * Helper untuk memeriksa hak akses Super Admin
     */
    private function checkIsSuperAdmin(?User $user): bool
    {
        if (!$user) return false;
        if ($user->username === 'superadmin' || $user->username === 'qa_superadmin') return true;
        if ($user->tenant_id === '00000000-0000-0000-0000-000000000000') return true;
        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) return true;
        if ($user->role && $user->role->nama_role === 'super_admin') return true;
        return false;
    }

    /**
     * Hitung ringkasan metrik status antrean
     */
    private function calculateMetrics(bool $isSuperAdmin, ?string $tenantId): array
    {
        $query = QueueJob::withoutTenant();

        if (!empty($tenantId)) {
            $query->where('tenant_id', $tenantId);
        }

        $counts = $query->select(
            DB::raw("SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending"),
            DB::raw("SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing"),
            DB::raw("SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed"),
            DB::raw("SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed"),
            DB::raw("COUNT(*) as total")
        )->first();

        return [
            'pending'    => (int)($counts->pending ?? 0),
            'processing' => (int)($counts->processing ?? 0),
            'completed'  => (int)($counts->completed ?? 0),
            'failed'     => (int)($counts->failed ?? 0),
            'total'      => (int)($counts->total ?? 0),
        ];
    }

    /**
     * Eksekutor logika tugas antrean (Dispatcher Simulation)
     */
    private function executeJobLogic(string $jobType, array $payload): void
    {
        // 1. Simulasi DEMO_SYNC (Pusdatin Data Sync)
        if ($jobType === 'DEMO_SYNC' || $jobType === 'DEMO_SYNC_FAIL' || $jobType === 'DEMO_SYNC_SUCCESS') {
            if (!empty($payload['force_fail']) || $jobType === 'DEMO_SYNC_FAIL') {
                throw new \Exception('Koneksi gateway Pusdatin timeout atau ditolak (Simulasi Gagal).');
            }
            // Simulasi operasi sukses
            usleep(100000); // 100ms
            return;
        }

        // 2. Simulasi DEMO_EMAIL (Blast Notifikasi / SPMB)
        if ($jobType === 'DEMO_EMAIL') {
            usleep(150000); // 150ms
            return;
        }

        // 3. Simulasi CLEANUP_SESSIONS (Pembersihan Sesi Kedaluwarsa)
        if ($jobType === 'CLEANUP_SESSIONS') {
            usleep(80000); // 80ms
            return;
        }

        // 4. Default execution
        usleep(50000);
    }
}
