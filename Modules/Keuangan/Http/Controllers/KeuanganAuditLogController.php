<?php

namespace Modules\Keuangan\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Core\Entities\Tenant;
use Modules\Keuangan\Entities\KeuanganAuditLog;
use Modules\Keuangan\Entities\TransaksiPembayaran;
use Modules\Keuangan\Entities\TagihanSiswa;
use Modules\Keuangan\Entities\KasBank;

class KeuanganAuditLogController extends Controller
{
    public function index(Request $request): InertiaResponse|JsonResponse
    {
        $user = Auth::user();
        $isSuperAdmin = $user ? $user->isSuperAdmin() : false;
        $roleName = strtolower($user?->role?->nama_role ?? '');
        $isSchoolStaff = in_array($roleName, ['admin', 'admin_sekolah', 'kepala_sekolah', 'keuangan', 'staf_keuangan', 'bendahara']);

        if (!$isSuperAdmin && !$isSchoolStaff) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Akses Terbatas: Halaman Audit Trail Keuangan hanya dapat diakses oleh Bagian Keuangan dan Administrator.'], 403);
            }
            abort(403, 'Akses Ditolak: Halaman Audit Trail Keuangan khusus untuk Bagian Keuangan dan Administrator.');
        }

        $selectedTenantId = $isSuperAdmin ? $request->query('tenant_id') : null;
        $tenantId = $selectedTenantId ?: (session('tenant_id') ?? $user?->tenant_id);

        $eventType = $request->query('event_type');
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');
        $search = trim((string)$request->query('search', ''));

        $query = KeuanganAuditLog::with('user:id,nama_lengkap,username,email');

        if (!empty($tenantId) && $tenantId !== '00000000-0000-0000-0000-000000000000') {
            $query->where('tenant_id', $tenantId);
        }

        if (!empty($eventType)) {
            $query->where('event_type', $eventType);
        }
        if (!empty($dateFrom)) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        if (!empty($dateTo)) {
            $query->whereDate('created_at', '<=', $dateTo);
        }
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('keterangan', 'ILIKE', "%{$search}%")
                  ->orWhere('ip_address', 'ILIKE', "%{$search}%");
            });
        }

        $logs = $query->orderBy('created_at', 'desc')->paginate(25);

        $metrics = [
            'total_log'        => KeuanganAuditLog::count(),
            'total_pembayaran' => KeuanganAuditLog::where('event_type', 'PAYMENT_SPP')->count(),
            'total_void'       => KeuanganAuditLog::where('event_type', 'VOID_PAYMENT')->count(),
            'total_modifikasi' => KeuanganAuditLog::whereIn('event_type', ['UPDATE_POS', 'GENERATE_TAGIHAN', 'DELETE_TAGIHAN'])->count(),
        ];

        $tenantsList = $isSuperAdmin ? Tenant::orderBy('nama_sekolah', 'asc')->get(['id', 'nama_sekolah']) : [];

        $data = [
            'logs'             => $logs,
            'metrics'          => $metrics,
            'isSuperAdmin'     => $isSuperAdmin,
            'tenantsList'      => $tenantsList,
            'selectedTenantId' => $selectedTenantId,
            'filters'          => [
                'tenant_id'  => $selectedTenantId,
                'event_type' => $eventType,
                'date_from'  => $dateFrom,
                'date_to'    => $dateTo,
                'search'     => $search,
            ]
        ];

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'data' => $data]);
        }

        return Inertia::render('Keuangan/AuditLog/Index', $data);
    }

    /**
     * Pembatalan Transaksi Kasir (Void Payment)
     */
    public function voidPayment(Request $request, string $transaksiId): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'alasan_void' => 'required|string|min:5|max:500',
        ]);

        $user = Auth::user();
        $tenantId = session('tenant_id') ?? $user?->tenant_id;

        return DB::transaction(function () use ($transaksiId, $validated, $user, $tenantId, $request) {
            $trx = TransaksiPembayaran::lockForUpdate()->findOrFail($transaksiId);

            if ($trx->status_transaksi === 'VOID') {
                return response()->json(['success' => false, 'message' => 'Transaksi ini sudah pernah dibatalkan sebelumnya.'], 422);
            }

            $nominalBayar = (float)$trx->nominal_bayar;

            // 1. Update Transaksi Menjadi VOID
            $trx->update([
                'status_transaksi' => 'VOID',
                'alasan_void'      => $validated['alasan_void'],
                'void_by'          => $user?->id,
                'void_at'          => now(),
            ]);

            // 2. Rollback Sisa & Status Tagihan Siswa
            if ($trx->tagihan_id) {
                $tagihan = TagihanSiswa::lockForUpdate()->find($trx->tagihan_id);
                if ($tagihan) {
                    $newTerbayar = max(0, (float)$tagihan->total_terbayar - $nominalBayar);
                    $newSisa = (float)$tagihan->total_tagihan - $newTerbayar;
                    $newStatus = ($newTerbayar <= 0) ? 'Belum Bayar' : 'Sebagian';

                    $tagihan->update([
                        'total_terbayar'    => $newTerbayar,
                        'sisa_tagihan'      => max(0, $newSisa),
                        'status_pembayaran' => $newStatus,
                    ]);
                }
            }

            // 3. Rollback Saldo Kas/Bank
            if ($trx->kas_id) {
                KasBank::where('id', $trx->kas_id)->decrement('saldo_saat_ini', $nominalBayar);
            }

            // 4. Catat Audit Trail
            KeuanganAuditLog::create([
                'tenant_id'  => $tenantId,
                'user_id'    => $user?->id,
                'user_role'  => $user?->role?->nama_role ?? 'admin',
                'event_type' => 'VOID_PAYMENT',
                'nominal'    => $nominalBayar,
                'old_data'   => ['nomor_transaksi' => $trx->nomor_transaksi, 'nominal' => $nominalBayar, 'status' => 'SUCCESS'],
                'new_data'   => ['nomor_transaksi' => $trx->nomor_transaksi, 'nominal' => $nominalBayar, 'status' => 'VOID', 'alasan' => $validated['alasan_void']],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'keterangan' => "Pembatalan Transaksi (Void) {$trx->nomor_transaksi}: Rp " . number_format($nominalBayar, 0, ',', '.') . ". Alasan: {$validated['alasan_void']}",
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "Transaksi {$trx->nomor_transaksi} berhasil dibatalkan dan saldo kas telah disesuaikan kembali."
                ]);
            }

            return back()->with('success', "Transaksi {$trx->nomor_transaksi} berhasil dibatalkan.");
        });
    }
}
