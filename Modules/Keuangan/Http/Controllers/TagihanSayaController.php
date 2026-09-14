<?php

namespace Modules\Keuangan\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Modules\Core\Entities\Tenant;
use Modules\Keuangan\Entities\TagihanSiswa;
use Modules\Keuangan\Entities\TransaksiPembayaran;
use Modules\Keuangan\Entities\PengaturanKeuangan;
use Modules\Siswa\Entities\Siswa;

class TagihanSayaController extends Controller
{
    public function index(Request $request): InertiaResponse|JsonResponse
    {
        $user = Auth::user();
        $isSuperAdmin = $user ? $user->isSuperAdmin() : false;
        $selectedTenantId = $request->query('tenant_id');
        $tenantId = ($isSuperAdmin && !empty($selectedTenantId)) ? $selectedTenantId : (session('tenant_id') ?? $user?->tenant_id);

        $tenantsList = $isSuperAdmin ? Tenant::orderBy('nama_sekolah', 'asc')->get(['id', 'nama_sekolah']) : [];

        // Cari data profil siswa yang terkait dengan user login
        $siswa = null;
        if (!$isSuperAdmin) {
            $siswa = Siswa::where(function ($q) use ($user) {
                    if ($user) {
                        $q->where('nisn', $user->username)
                          ->orWhere('nis', $user->username)
                          ->orWhere('email', $user->email);
                    }
                })
                ->first();
        }

        // Fallback untuk Super Admin / Preview tenant terpilih
        if (!$siswa) {
            $siswa = Siswa::where('tenant_id', $tenantId)
                ->where('status_siswa', 'Aktif')
                ->first();
        }

        $tagihanList = [];
        $riwayatTransaksi = [];
        $totalTunggakan = 0;
        $totalTerbayar = 0;

        if ($siswa) {
            $tagihanList = TagihanSiswa::when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))->with('pos:id,nama_pos,tipe_periode')
                ->where('siswa_id', $siswa->id)
                ->orderBy('tahun', 'desc')
                ->orderBy('bulan', 'desc')
                ->orderBy('created_at', 'desc')
                ->get();

            $totalTunggakan = $tagihanList->whereIn('status_pembayaran', ['Belum Bayar', 'Sebagian'])->sum('sisa_tagihan');
            $totalTerbayar = $tagihanList->sum('total_terbayar');

            $riwayatTransaksi = TransaksiPembayaran::when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))->with(['tagihan.pos', 'kas', 'kasir:id,nama_lengkap'])
                ->where('siswa_id', $siswa->id)
                ->where('status_transaksi', 'SUCCESS')
                ->orderBy('tanggal_bayar', 'desc')
                ->limit(20)
                ->get();
        }

        $pengaturan = PengaturanKeuangan::where('tenant_id', $tenantId)->first();

        $data = [
            'siswa'            => $siswa,
            'tagihanList'      => $tagihanList,
            'riwayatTransaksi' => $riwayatTransaksi,
            'totalTunggakan'   => (float)$totalTunggakan,
            'totalTerbayar'    => (float)$totalTerbayar,
            'pengaturan'       => $pengaturan,
            'isSuperAdmin'     => $isSuperAdmin,
            'tenantsList'      => $tenantsList,
            'selectedTenantId' => $selectedTenantId,
        ];

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'data' => $data]);
        }

        return Inertia::render('Keuangan/TagihanSaya/Index', $data);
    }
}
