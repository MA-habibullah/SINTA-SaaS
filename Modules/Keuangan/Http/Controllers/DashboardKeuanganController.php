<?php

namespace Modules\Keuangan\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Core\Entities\Tenant;
use Modules\Keuangan\Entities\TransaksiPembayaran;
use Modules\Keuangan\Entities\TagihanSiswa;
use Modules\Keuangan\Entities\PosKeuangan;
use Modules\Keuangan\Entities\KasBank;

class DashboardKeuanganController extends Controller
{
    public function index(Request $request): InertiaResponse|JsonResponse
    {
        $user = Auth::user();
        $isSuperAdmin = $user ? $user->isSuperAdmin() : false;
        $selectedTenantId = $request->query('tenant_id');
        $tenantId = ($isSuperAdmin && !empty($selectedTenantId)) ? $selectedTenantId : (session('tenant_id') ?? $user?->tenant_id);

        $tenantsList = $isSuperAdmin ? Tenant::orderBy('nama_sekolah', 'asc')->get(['id', 'nama_sekolah']) : [];

        // 1. Pemasukan Hari Ini
        $pemasukanHariIni = TransaksiPembayaran::where('status_transaksi', 'SUCCESS')
            ->when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))
            ->whereDate('tanggal_bayar', now()->toDateString())
            ->sum('nominal_bayar');

        // 2. Pemasukan Bulan Ini
        $pemasukanBulanIni = TransaksiPembayaran::where('status_transaksi', 'SUCCESS')
            ->when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))
            ->whereMonth('tanggal_bayar', now()->month)
            ->whereYear('tanggal_bayar', now()->year)
            ->sum('nominal_bayar');

        // 3. Total Tunggakan Aktif
        $totalTunggakan = TagihanSiswa::whereIn('status_pembayaran', ['Belum Bayar', 'Sebagian'])
            ->when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))
            ->sum('sisa_tagihan');

        // 4. Total Tagihan Keseluruhan & Terbayar
        $totalTagihanAll = TagihanSiswa::when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))->sum('total_tagihan');
        $totalTerbayarAll = TagihanSiswa::when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))->sum('total_terbayar');

        // 5. Total Saldo Kas & Bank
        $totalSaldoKas = KasBank::where('is_active', true)
            ->when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))
            ->sum('saldo_saat_ini');

        // 6. Progres Pelunasan per Kelas
        $progresKelas = DB::table('keuangan.transaksi_spp_tagihan as t')
            ->join('siswa.siswa as s', 't.siswa_id', '=', 's.id')
            ->select(
                DB::raw("COALESCE(NULLIF(s.kelas_saat_ini, ''), 'Tanpa Kelas') as nama_kelas"),
                DB::raw("SUM(t.total_tagihan) as total_tagihan"),
                DB::raw("SUM(t.total_terbayar) as total_bayar"),
                DB::raw("SUM(t.sisa_tagihan) as total_tunggakan"),
                DB::raw("COUNT(DISTINCT t.siswa_id) as jumlah_siswa")
            )
            ->when($tenantId, fn($q) => $q->where('t.tenant_id', $tenantId))
            ->groupBy('s.kelas_saat_ini')
            ->orderBy('s.kelas_saat_ini', 'asc')
            ->get();

        // 7. Transaksi Terakhir (Recent 8)
        $recentTransactions = TransaksiPembayaran::with(['siswa:id,nama_lengkap,nisn', 'kas:id,nama_kas', 'kasir:id,nama_lengkap'])
            ->where('status_transaksi', 'SUCCESS')
            ->when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))
            ->orderBy('tanggal_bayar', 'desc')
            ->limit(8)
            ->get();

        $data = [
            'metrics' => [
                'pemasukan_hari_ini'  => (float)$pemasukanHariIni,
                'pemasukan_bulan_ini' => (float)$pemasukanBulanIni,
                'total_tunggakan'     => (float)$totalTunggakan,
                'total_tagihan_all'   => (float)$totalTagihanAll,
                'total_terbayar_all'  => (float)$totalTerbayarAll,
                'total_saldo_kas'     => (float)$totalSaldoKas,
                'progres_kelas'       => $progresKelas,
                'recent_transactions' => $recentTransactions,
            ],
            'isSuperAdmin'     => $isSuperAdmin,
            'tenantsList'      => $tenantsList,
            'selectedTenantId' => $selectedTenantId,
        ];

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'data' => $data]);
        }

        return Inertia::render('Keuangan/Dashboard/Index', $data);
    }
}
