<?php

namespace Modules\Keuangan\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\Payment\PaymentGatewayService;
use App\Services\Notification\WhatsAppGatewayService;
use Modules\Keuangan\Entities\TagihanSiswa;
use Modules\Keuangan\Entities\TransaksiPembayaran;
use Modules\Keuangan\Entities\KasBank;

class PaymentWebhookController extends Controller
{
    /**
     * Webhook Callback Handler untuk Midtrans
     */
    public function handleMidtransCallback(Request $request, PaymentGatewayService $paymentService, WhatsAppGatewayService $waService): JsonResponse
    {
        $payload = $request->all();
        $orderId = $payload['order_id'] ?? '';
        $statusCode = $payload['status_code'] ?? '';
        $grossAmount = $payload['gross_amount'] ?? '';
        $signatureKey = $payload['signature_key'] ?? '';
        $transactionStatus = $payload['transaction_status'] ?? '';
        $paymentType = $payload['payment_type'] ?? 'Payment Gateway';

        // 1. Verifikasi Signature HMAC
        if (!$paymentService->verifyMidtransSignature($orderId, $statusCode, $grossAmount, $signatureKey)) {
            Log::warning("Midtrans Webhook: Invalid Signature Key for Order {$orderId}");
            return response()->json(['success' => false, 'message' => 'Invalid signature.'], 403);
        }

        // Ekstrak tagihan ID dari Order ID (e.g., INV-[TAGIHAN_ID]-[TIMESTAMP])
        preg_match('/INV-([a-f0-9\-]+)-/', $orderId, $matches);
        $tagihanId = $matches[1] ?? null;

        if (!$tagihanId) {
            return response()->json(['success' => false, 'message' => 'Order ID format unrecognized.'], 400);
        }

        // 2. Proses Status Settlement / Lunas
        if (in_array($transactionStatus, ['settlement', 'capture'])) {
            return DB::transaction(function () use ($tagihanId, $grossAmount, $paymentType, $orderId, $waService) {
                $tagihan = TagihanSiswa::withoutTenant()->lockForUpdate()->find($tagihanId);
                if (!$tagihan) {
                    return response()->json(['success' => false, 'message' => 'Tagihan not found.'], 404);
                }

                if ($tagihan->status_pembayaran === 'Lunas') {
                    return response()->json(['success' => true, 'message' => 'Already settled.']);
                }

                $nominalBayar = (float)$grossAmount;

                // Simpan transaksi
                $transaksi = TransaksiPembayaran::create([
                    'tenant_id'         => $tagihan->tenant_id,
                    'nomor_transaksi'   => $orderId,
                    'tagihan_id'        => $tagihan->id,
                    'siswa_id'          => $tagihan->siswa_id,
                    'nominal_bayar'     => $nominalBayar,
                    'metode_pembayaran' => 'Payment Gateway (' . $paymentType . ')',
                    'tanggal_bayar'     => now(),
                    'user_id_kasir'     => null,
                    'catatan'           => 'Auto Settlement Midtrans Payment Gateway',
                ]);

                // Update Tagihan
                $newTerbayar = (float)$tagihan->total_terbayar + $nominalBayar;
                $newSisa = max(0, (float)$tagihan->total_tagihan - $newTerbayar);
                $status = ($newSisa <= 0) ? 'Lunas' : 'Sebagian';

                $tagihan->update([
                    'total_terbayar'    => $newTerbayar,
                    'sisa_tagihan'      => $newSisa,
                    'status_pembayaran' => $status,
                ]);

                // Update Kas Utama
                KasBank::withoutTenant()
                    ->where('tenant_id', $tagihan->tenant_id)
                    ->where('is_active', true)
                    ->increment('saldo_saat_ini', $nominalBayar);

                // Kirim notifikasi WA ke ortu/wali jika ada nomor HP
                $siswa = $tagihan->siswa;
                $noHp = $siswa->no_hp_ayah ?? $siswa->no_hp ?? '';
                if (!empty($noHp)) {
                    $waService->sendPaymentReceiptNotification(
                        $noHp,
                        $siswa->nama_lengkap,
                        $tagihan->pos?->nama_pos ?? 'SPP',
                        $nominalBayar,
                        $orderId
                    );
                }

                return response()->json(['success' => true, 'message' => 'Payment settlement processed successfully.']);
            });
        }

        return response()->json(['success' => true, 'message' => 'Webhook received with status: ' . $transactionStatus]);
    }
}
