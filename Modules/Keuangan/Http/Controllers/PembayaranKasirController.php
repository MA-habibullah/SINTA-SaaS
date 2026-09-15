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
use Modules\Keuangan\Entities\TagihanSiswa;
use Modules\Keuangan\Entities\TransaksiPembayaran;
use Modules\Keuangan\Entities\KasBank;
use Modules\Keuangan\Entities\PengaturanKeuangan;
use Modules\Keuangan\Entities\KeuanganAuditLog;
use Modules\Siswa\Entities\Siswa;
use App\Services\SecurityPayloadService;

class PembayaranKasirController extends Controller
{
    /**
     * Halaman Loket Kasir Pembayaran Real-time
     * Zero-SSR Pattern: initial GET hanya render shell, data dimuat via ?async=1
     */
    public function index(Request $request): InertiaResponse|JsonResponse
    {
        $user = Auth::user();
        $isSuperAdmin = $user ? $user->isSuperAdmin() : false;

        // === ZERO-SSR: Initial page load hanya render shell kosong ===
        $isInitialSsr = !$request->header('X-Inertia') && !$request->has('async');

        if ($isInitialSsr) {
            return Inertia::render('Keuangan/Kasir/Index', [
                'kasList'      => null,
                'kelasList'    => null,
                'pengaturan'   => null,
                'isSuperAdmin' => $isSuperAdmin,
                'tenantsList'  => null,
            ]);
        }

        // === ASYNC JSON: Data aktual dimuat on-demand via Axios ===
        // Tenant ditentukan dari sesi server, bukan dari URL query parameter
        $selectedTenantId = $request->header('X-Tenant-Id') ?? $request->query('async_tenant_id');
        $tenantId = ($isSuperAdmin && !empty($selectedTenantId))
            ? $selectedTenantId
            : (session('tenant_id') ?? $user?->tenant_id);

        $kasList         = KasBank::when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))->where('is_active', true)->orderBy('created_at', 'asc')->get();
        $kelasList       = DB::table('akademik.kelas')->when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))->where('is_active', true)->orderBy('nama_kelas', 'asc')->get(['id', 'nama_kelas', 'kode_kelas']);
        $pengaturan      = PengaturanKeuangan::where('tenant_id', $tenantId)->first();
        $tenantsList     = $isSuperAdmin ? Tenant::orderBy('nama_sekolah', 'asc')->get(['id', 'nama_sekolah']) : [];

        $payload = [
            'kasList'      => $kasList,
            'kelasList'    => $kelasList,
            'pengaturan'   => $pengaturan,
            'isSuperAdmin' => $isSuperAdmin,
            'tenantsList'  => $tenantsList,
        ];

        // Sanitasi: hilangkan data internal sensitif sebelum kirim ke frontend
        $payload = SecurityPayloadService::sanitize($payload);

        if ($request->wantsJson() || $request->has('async')) {
            return response()->json(['success' => true, 'data' => $payload]);
        }

        return Inertia::render('Keuangan/Kasir/Index', $payload);
    }

    /**
     * API: Cari Siswa via NISN/Nama (dipanggil dari SearchableSelect)
     */
    public function searchSiswa(Request $request): JsonResponse
    {
        $user = Auth::user();
        $isSuperAdmin = $user ? $user->isSuperAdmin() : false;
        $selectedTenantId = $request->header('X-Tenant-Id') ?? $request->query('async_tenant_id');
        $tenantId = ($isSuperAdmin && !empty($selectedTenantId))
            ? $selectedTenantId
            : (session('tenant_id') ?? $user?->tenant_id);

        $q = trim((string)$request->query('q', ''));
        $kelasId = $request->query('kelas_id');

        $query = Siswa::where('tenant_id', $tenantId)
            ->where('status_siswa', 'Aktif')
            ->where(function ($sub) use ($q) {
                $sub->where('nama_lengkap', 'ILIKE', "%{$q}%")
                    ->orWhere('nisn', 'ILIKE', "%{$q}%")
                    ->orWhere('nis', 'ILIKE', "%{$q}%");
            })
            ->limit(15)
            ->get(['id', 'nama_lengkap', 'nisn', 'nis', 'kelas_saat_ini']);

        return response()->json(['success' => true, 'data' => $query]);
    }

    /**
     * API Ambil Tagihan Siswa Terpilih (on-demand per siswa)
     */
    public function getSiswaTagihan(Request $request, string $siswaId): JsonResponse
    {
        $siswa = Siswa::find($siswaId);

        if (!$siswa) {
            return response()->json(['success' => false, 'message' => 'Siswa tidak ditemukan.'], 404);
        }

        $tagihanList = TagihanSiswa::with('pos:id,nama_pos,tipe_periode')
            ->where('siswa_id', $siswaId)
            ->whereIn('status_pembayaran', ['Belum Bayar', 'Sebagian'])
            ->orderBy('tahun', 'asc')
            ->orderBy('bulan', 'asc')
            ->orderBy('created_at', 'asc')
            ->get();

        $riwayatTransaksi = TransaksiPembayaran::with(['tagihan.pos', 'kas', 'kasir:id,nama_lengkap'])
            ->where('siswa_id', $siswaId)
            ->where('status_transaksi', 'SUCCESS')
            ->orderBy('tanggal_bayar', 'desc')
            ->limit(15)
            ->get();

        // Sanitasi: hilangkan field sensitif sebelum kirim ke frontend
        $responsePayload = SecurityPayloadService::sanitize([
            'siswa'            => $siswa,
            'tagihanList'      => $tagihanList,
            'riwayatTransaksi' => $riwayatTransaksi,
        ]);

        return response()->json(array_merge(['success' => true], $responsePayload));
    }

    /**
     * Proses Pembayaran Multi-Invoice Kasir Real-time
     */
    public function bayar(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'siswa_id'          => 'required|uuid|exists:siswa.siswa,id',
            'metode_pembayaran' => 'required|string|in:Tunai,Transfer Bank,QRIS,Midtrans VA',
            'kas_id'            => 'required|uuid|exists:keuangan.kas_bank,id',
            'catatan'           => 'nullable|string',
            'items'             => 'required|array|min:1',
            'items.*.tagihan_id'    => 'required|uuid|exists:keuangan.transaksi_spp_tagihan,id',
            'items.*.nominal_bayar' => 'required|numeric|min:100',
        ]);

        $user = Auth::user();
        $tenantId = session('tenant_id') ?? $user?->tenant_id;

        return DB::transaction(function () use ($validated, $user, $tenantId, $request) {
            $createdTransactions = [];
            $totalBayarSemua = 0;
            $nomorTrxBase = 'TRX/' . date('Ymd') . '/' . strtoupper(Str::random(6));

            foreach ($validated['items'] as $index => $item) {
                $tagihan = TagihanSiswa::lockForUpdate()->findOrFail($item['tagihan_id']);
                $nominalBayar = (float)$item['nominal_bayar'];

                if ($nominalBayar > (float)$tagihan->sisa_tagihan) {
                    abort(422, "Nominal bayar (Rp " . number_format($nominalBayar, 0, ',', '.') . ") melebihi sisa tagihan untuk pos {$tagihan->pos?->nama_pos} (Rp " . number_format($tagihan->sisa_tagihan, 0, ',', '.') . ").");
                }

                $nomorTrx = (count($validated['items']) > 1) ? "{$nomorTrxBase}-" . ($index + 1) : $nomorTrxBase;

                // 1. Catat Transaksi Pembayaran
                $trx = TransaksiPembayaran::create([
                    'tenant_id'         => $tenantId,
                    'nomor_transaksi'   => $nomorTrx,
                    'tagihan_id'        => $tagihan->id,
                    'siswa_id'          => $validated['siswa_id'],
                    'nominal_bayar'     => $nominalBayar,
                    'metode_pembayaran' => $validated['metode_pembayaran'],
                    'kas_id'            => $validated['kas_id'],
                    'user_id_kasir'     => $user?->id,
                    'tanggal_bayar'     => now(),
                    'status_transaksi'  => 'SUCCESS',
                    'catatan'           => $validated['catatan'] ?? null,
                ]);

                // 2. Update Status dan Saldo Tagihan
                $newTerbayar = (float)$tagihan->total_terbayar + $nominalBayar;
                $newSisa = (float)$tagihan->total_tagihan - $newTerbayar;
                $newStatus = ($newSisa <= 0) ? 'Lunas' : 'Sebagian';

                $tagihan->update([
                    'total_terbayar'    => $newTerbayar,
                    'sisa_tagihan'      => max(0, $newSisa),
                    'status_pembayaran' => $newStatus,
                ]);

                $createdTransactions[] = $trx->load(['tagihan.pos', 'kas', 'kasir:id,nama_lengkap', 'siswa.kelas']);
                $totalBayarSemua += $nominalBayar;
            }

            // 3. Update Saldo Kas/Bank
            KasBank::where('id', $validated['kas_id'])->increment('saldo_saat_ini', $totalBayarSemua);

            // 4. Catat Audit Log
            KeuanganAuditLog::create([
                'tenant_id'  => $tenantId,
                'user_id'    => $user?->id,
                'user_role'  => $user?->role?->nama_role ?? 'kasir',
                'event_type' => 'PAYMENT_SPP',
                'nominal'    => $totalBayarSemua,
                'new_data'   => [
                    'nomor_transaksi_base' => $nomorTrxBase,
                    'jumlah_tagihan'       => count($validated['items']),
                    'total_nominal'        => $totalBayarSemua,
                    'metode'               => $validated['metode_pembayaran'],
                    'kas_id'               => $validated['kas_id'],
                    'siswa_id'             => $validated['siswa_id'],
                ],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'keterangan' => "Pembayaran Kasir: Rp " . number_format($totalBayarSemua, 0, ',', '.') . " ({$validated['metode_pembayaran']})",
            ]);

            $siswa = Siswa::with('kelas')->find($validated['siswa_id']);
            $kas = KasBank::find($validated['kas_id']);
            $pengaturan = PengaturanKeuangan::where('tenant_id', $tenantId)->first();

            $kuitansiPayload = [
                'nomor_kuitansi'     => $nomorTrxBase,
                'tanggal'            => now()->translatedFormat('d F Y H:i'),
                'siswa'              => $siswa,
                'kas'                => $kas,
                'kasir'              => $user?->nama_lengkap,
                'metode_pembayaran'  => $validated['metode_pembayaran'],
                'catatan'            => $validated['catatan'] ?? '-',
                'items'              => $createdTransactions,
                'total_bayar'        => $totalBayarSemua,
                'nama_bendahara'     => $pengaturan?->nama_bendahara ?? 'Bendahara',
                'catatan_kuitansi'   => $pengaturan?->catatan_kuitansi ?? '',
            ];

            // Sanitasi kuitansi payload sebelum dikirim ke frontend
            $kuitansiPayload = SecurityPayloadService::sanitize($kuitansiPayload);

            if ($request->wantsJson()) {
                return response()->json([
                    'success'   => true,
                    'message'   => 'Pembayaran berhasil diproses dan kuitansi telah siap dicetak.',
                    'kuitansi'  => $kuitansiPayload,
                ], 201);
            }

            return back()->with('success', 'Pembayaran kasir berhasil disimpan.');
        });
    }
}
