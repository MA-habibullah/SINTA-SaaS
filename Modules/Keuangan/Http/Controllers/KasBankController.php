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
use Illuminate\Support\Str;
use Modules\Core\Entities\Tenant;
use Modules\Keuangan\Entities\KasBank;
use Modules\Keuangan\Entities\TransaksiPembayaran;
use Modules\Keuangan\Entities\KeuanganAuditLog;
use App\Services\SecurityPayloadService;

class KasBankController extends Controller
{
    /**
     * Halaman Buku Kas & Rekening Bank
     * Zero-SSR Pattern: initial GET render shell, data via ?async=1
     */
    public function index(Request $request): InertiaResponse|JsonResponse
    {
        $user = Auth::user();
        $isSuperAdmin = $user ? $user->isSuperAdmin() : false;

        // === ZERO-SSR: Shell kosong untuk initial request ===
        $isInitialSsr = !$request->header('X-Inertia') && !$request->has('async');

        if ($isInitialSsr) {
            return Inertia::render('Keuangan/KasBankPage/Index', [
                'kasList'      => null,
                'isSuperAdmin' => $isSuperAdmin,
                'tenantsList'  => null,
            ]);
        }

        // === ASYNC JSON: Data aktual ===
        $selectedTenantId = $request->header('X-Tenant-Id') ?? $request->query('async_tenant_id');
        $tenantId = ($isSuperAdmin && !empty($selectedTenantId))
            ? $selectedTenantId
            : (session('tenant_id') ?? $user?->tenant_id);

        $kasList     = KasBank::when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))->orderBy('created_at', 'asc')->get();
        $tenantsList = $isSuperAdmin ? Tenant::orderBy('nama_sekolah', 'asc')->get(['id', 'nama_sekolah']) : [];

        $payload = SecurityPayloadService::sanitize([
            'kasList'      => $kasList,
            'isSuperAdmin' => $isSuperAdmin,
            'tenantsList'  => $tenantsList,
        ]);

        return response()->json(['success' => true, 'data' => $payload]);
    }

    /**
     * API: Ambil Jurnal Transaksi Kas (per Rekening)
     */
    public function getJurnalKas(Request $request, string $kasId): JsonResponse
    {
        $user = Auth::user();
        $tenantId = session('tenant_id') ?? $user?->tenant_id;

        $kas = KasBank::where('id', $kasId)->where('tenant_id', $tenantId)->firstOrFail();

        $jurnal = TransaksiPembayaran::with(['siswa:id,nama_lengkap,nisn', 'tagihan.pos:id,nama_pos', 'kasir:id,nama_lengkap'])
            ->where('kas_id', $kasId)
            ->where('status_transaksi', 'SUCCESS')
            ->when($request->query('tanggal_dari'), fn($q, $v) => $q->whereDate('tanggal_bayar', '>=', $v))
            ->when($request->query('tanggal_sampai'), fn($q, $v) => $q->whereDate('tanggal_bayar', '<=', $v))
            ->orderBy('tanggal_bayar', 'desc')
            ->paginate(30);

        // Hitung rekap statistik
        $statsQuery = TransaksiPembayaran::where('kas_id', $kasId)->where('status_transaksi', 'SUCCESS');
        $totalPemasukan = $statsQuery->sum('nominal_bayar');

        return response()->json([
            'success'        => true,
            'kas'            => $kas,
            'jurnal'         => $jurnal,
            'total_pemasukan' => $totalPemasukan,
        ]);
    }

    /**
     * Simpan Rekening / Kas Baru
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'kode_kas'       => 'required|string|max:20',
            'nama_kas'       => 'required|string|max:100',
            'nomor_rekening' => 'nullable|string|max:50',
            'atas_nama'      => 'nullable|string|max:100',
            'saldo_awal'     => 'required|numeric|min:0',
            'is_active'      => 'boolean',
        ]);

        $user = Auth::user();
        $tenantId = session('tenant_id') ?? $user?->tenant_id;

        $kas = KasBank::create(array_merge($validated, [
            'tenant_id'     => $tenantId,
            'saldo_saat_ini' => $validated['saldo_awal'],
        ]));

        KeuanganAuditLog::create([
            'tenant_id'  => $tenantId,
            'user_id'    => $user?->id,
            'user_role'  => $user?->role?->nama_role ?? 'admin',
            'event_type' => 'KAS_BANK_CREATE',
            'nominal'    => $validated['saldo_awal'],
            'new_data'   => $validated,
            'ip_address' => $request->ip(),
            'keterangan' => "Tambah Rekening Kas: {$validated['nama_kas']}",
        ]);

        return response()->json(['success' => true, 'message' => 'Rekening kas berhasil ditambahkan.', 'data' => $kas], 201);
    }

    /**
     * Update Rekening / Kas
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'kode_kas'       => 'required|string|max:20',
            'nama_kas'       => 'required|string|max:100',
            'nomor_rekening' => 'nullable|string|max:50',
            'atas_nama'      => 'nullable|string|max:100',
            'is_active'      => 'boolean',
        ]);

        $user = Auth::user();
        $tenantId = session('tenant_id') ?? $user?->tenant_id;
        $kas = KasBank::where('id', $id)->where('tenant_id', $tenantId)->firstOrFail();
        $kas->update($validated);

        return response()->json(['success' => true, 'message' => 'Rekening kas berhasil diperbarui.', 'data' => $kas]);
    }

    /**
     * Hapus Rekening Kas (hanya jika tidak ada transaksi)
     */
    public function destroy(string $id): JsonResponse
    {
        $user = Auth::user();
        $tenantId = session('tenant_id') ?? $user?->tenant_id;
        $kas = KasBank::where('id', $id)->where('tenant_id', $tenantId)->firstOrFail();

        $hasTransaksi = TransaksiPembayaran::where('kas_id', $id)->exists();
        if ($hasTransaksi) {
            return response()->json(['success' => false, 'message' => 'Rekening kas tidak dapat dihapus karena masih memiliki riwayat transaksi.'], 422);
        }

        $kas->delete();
        return response()->json(['success' => true, 'message' => 'Rekening kas berhasil dihapus.']);
    }
}
