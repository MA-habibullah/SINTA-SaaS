<?php

namespace Modules\Keuangan\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Modules\Keuangan\Entities\TagihanSiswa;
use Modules\Keuangan\Entities\TransaksiPembayaran;
use Modules\Keuangan\Entities\KasBank;
use Modules\Siswa\Entities\Siswa;

class PembayaranKasirController extends Controller
{
    /**
     * Tampilan Kasir Pembayaran Siswa Real-time
     */
    public function index(Request $request): InertiaResponse|JsonResponse
    {
        $siswaId = $request->input('siswa_id');
        $siswa = null;
        $tagihanList = [];

        if (!empty($siswaId)) {
            $siswa = Siswa::find($siswaId);
            if ($siswa) {
                $tagihanList = TagihanSiswa::with('pos')
                    ->where('siswa_id', $siswaId)
                    ->whereIn('status_pembayaran', ['Belum Bayar', 'Sebagian'])
                    ->orderBy('created_at', 'asc')
                    ->get();
            }
        }

        $kasList = KasBank::where('is_active', true)->get();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => compact('siswa', 'tagihanList', 'kasList'),
            ]);
        }

        return Inertia::render('Keuangan/Kasir/Index', [
            'siswa'       => $siswa,
            'tagihanList' => $tagihanList,
            'kasList'     => $kasList,
        ]);
    }

    /**
     * Proses Transaksi Pembayaran Kasir
     */
    public function bayar(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'tagihan_id'        => 'required|uuid|exists:keuangan.tagihan_siswa,id',
            'nominal_bayar'     => 'required|numeric|min:1000',
            'metode_pembayaran' => 'required|string|in:Tunai,Transfer Bank,QRIS,Payment Gateway',
            'kas_id'            => 'nullable|uuid|exists:keuangan.kas_bank,id',
            'catatan'           => 'nullable|string',
        ]);

        return DB::transaction(function () use ($validated, $request) {
            $tagihan = TagihanSiswa::lockForUpdate()->findOrFail($validated['tagihan_id']);
            $nominalBayar = (float)$validated['nominal_bayar'];

            if ($nominalBayar > (float)$tagihan->sisa_tagihan) {
                abort(422, 'Nominal bayar melebihi sisa tagihan (' . number_format($tagihan->sisa_tagihan, 0) . ').');
            }

            // 1. Simpan Transaksi Pembayaran
            $transaksi = TransaksiPembayaran::create([
                'tenant_id'         => session('tenant_id'),
                'nomor_transaksi'   => 'TRX/' . date('Ymd') . '/' . rand(100000, 999999),
                'tagihan_id'        => $tagihan->id,
                'siswa_id'          => $tagihan->siswa_id,
                'nominal_bayar'     => $nominalBayar,
                'metode_pembayaran' => $validated['metode_pembayaran'],
                'tanggal_bayar'     => now(),
                'kas_id'            => $validated['kas_id'] ?? null,
                'user_id_kasir'     => auth()->id(),
                'catatan'           => $validated['catatan'] ?? null,
            ]);

            // 2. Update Status dan Sisa Tagihan
            $newTerbayar = (float)$tagihan->total_terbayar + $nominalBayar;
            $newSisa = (float)$tagihan->total_tagihan - $newTerbayar;
            $status = ($newSisa <= 0) ? 'Lunas' : 'Sebagian';

            $tagihan->update([
                'total_terbayar'    => $newTerbayar,
                'sisa_tagihan'      => max(0, $newSisa),
                'status_pembayaran' => $status,
            ]);

            // 3. Update Saldo Kas
            if (!empty($validated['kas_id'])) {
                KasBank::where('id', $validated['kas_id'])->increment('saldo_saat_ini', $nominalBayar);
            }

            if ($request->wantsJson()) {
                return response()->json([
                    'success'   => true,
                    'message'   => 'Pembayaran berhasil diproses.',
                    'transaksi' => $transaksi,
                ], 201);
            }

            return back()->with('success', 'Pembayaran berhasil disimpan.');
        });
    }
}
