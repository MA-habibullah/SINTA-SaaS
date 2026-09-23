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
use Modules\Keuangan\Entities\PengaturanKeuangan;

class LaporanKeuanganController extends Controller
{
    public function index(Request $request): InertiaResponse|JsonResponse
    {
        $user = Auth::user();
        $isSuperAdmin = $user ? $user->isSuperAdmin() : false;
        $selectedTenantId = $request->query('tenant_id');
        $tenantId = ($isSuperAdmin && !empty($selectedTenantId)) ? $selectedTenantId : (session('tenant_id') ?? $user?->tenant_id);

        $tab = $request->query('tab', 'pemasukan'); // 'pemasukan', 'tunggakan', 'surat'
        $dateFrom = $request->query('date_from', now()->startOfMonth()->toDateString());
        $dateTo = $request->query('date_to', now()->toDateString());
        $posId = $request->query('pos_id');
        $kelasId = $request->query('kelas_id');
        $kasId = $request->query('kas_id');
        $metode = $request->query('metode');

        // 1. Data Rekap Pemasukan (Buku Kas Umum)
        $pemasukanQuery = TransaksiPembayaran::when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))->with([
            'siswa:id,nama_lengkap,nisn,kelas_saat_ini',
            'tagihan.pos:id,nama_pos,tipe_periode',
            'kas:id,nama_kas',
            'kasir:id,nama_lengkap'
        ])
        ->where('status_transaksi', 'SUCCESS')
        ->whereDate('tanggal_bayar', '>=', $dateFrom)
        ->whereDate('tanggal_bayar', '<=', $dateTo);

        if (!empty($kasId)) {
            $pemasukanQuery->where('kas_id', $kasId);
        }
        if (!empty($metode)) {
            $pemasukanQuery->where('metode_pembayaran', $metode);
        }
        if (!empty($posId)) {
            $pemasukanQuery->whereHas('tagihan', function ($q) use ($posId) {
                $q->where('pos_id', $posId);
            });
        }
        if (!empty($kelasId)) {
            $kelasObj = DB::table('akademik.kelas')->where('id', $kelasId)->first();
            $namaKelas = $kelasObj ? $kelasObj->nama_kelas : $kelasId;
            $pemasukanQuery->whereHas('siswa', function ($q) use ($namaKelas) {
                $q->where('kelas_saat_ini', $namaKelas);
            });
        }

        $pemasukanList = $pemasukanQuery->orderBy('tanggal_bayar', 'desc')->paginate(25);
        $totalPemasukanNominal = (clone $pemasukanQuery)->sum('nominal_bayar');

        // 2. Data Rekap Tunggakan
        $tunggakanQuery = TagihanSiswa::when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))->with([
            'siswa:id,nama_lengkap,nisn,nis,kelas_saat_ini,no_telepon_orang_tua',
            'pos:id,nama_pos,tipe_periode'
        ])
        ->whereIn('status_pembayaran', ['Belum Bayar', 'Sebagian']);

        if (!empty($posId)) {
            $tunggakanQuery->where('pos_id', $posId);
        }
        if (!empty($kelasId)) {
            $kelasObj = DB::table('akademik.kelas')->where('id', $kelasId)->first();
            $namaKelas = $kelasObj ? $kelasObj->nama_kelas : $kelasId;
            $tunggakanQuery->whereHas('siswa', function ($q) use ($namaKelas) {
                $q->where('kelas_saat_ini', $namaKelas);
            });
        }

        $tunggakanList = $tunggakanQuery->orderBy('sisa_tagihan', 'desc')->paginate(25);
        $totalTunggakanNominal = (clone $tunggakanQuery)->sum('sisa_tagihan');

        $posList = PosKeuangan::when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))->where('is_active', true)->get(['id', 'nama_pos', 'tipe_periode']);
        $kelasList = DB::table('akademik.kelas')->when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))->where('is_active', true)->orderBy('nama_kelas', 'asc')->get(['id', 'nama_kelas', 'kode_kelas']);
        $kasList = KasBank::when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))->where('is_active', true)->get(['id', 'nama_kas', 'saldo_saat_ini']);
        $pengaturan = PengaturanKeuangan::where('tenant_id', $tenantId)->first();
        $tenantsList = $isSuperAdmin ? Tenant::orderBy('nama_sekolah', 'asc')->get(['id', 'nama_sekolah']) : [];

        $data = [
            'activeTab'             => $tab,
            'pemasukanList'         => $pemasukanList,
            'totalPemasukanNominal' => (float)$totalPemasukanNominal,
            'tunggakanList'         => $tunggakanList,
            'totalTunggakanNominal' => (float)$totalTunggakanNominal,
            'posList'               => $posList,
            'kelasList'             => $kelasList,
            'kasList'               => $kasList,
            'pengaturan'            => $pengaturan,
            'isSuperAdmin'          => $isSuperAdmin,
            'tenantsList'           => $tenantsList,
            'selectedTenantId'      => $selectedTenantId,
            'allowed_tabs'          => \Modules\Core\Services\MenuService::getAllowedTabsForRoute($user, '/keuangan/laporan'),
            'filters'               => [
                'tenant_id' => $selectedTenantId,
                'date_from' => $dateFrom,
                'date_to'   => $dateTo,
                'pos_id'    => $posId,
                'kelas_id'  => $kelasId,
                'kas_id'    => $kasId,
                'metode'    => $metode,
            ]
        ];

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'data' => $data]);
        }

        return Inertia::render('Keuangan/Laporan/Index', $data);
    }
}
