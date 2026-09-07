<?php

namespace App\Services\Payment;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Keuangan\Entities\TagihanSiswa;

class PaymentGatewayService
{
    /**
     * Membuat Snap Payment Token / URL (Midtrans)
     */
    public function createSnapTransaction(TagihanSiswa $tagihan): ?array
    {
        $serverKey = config('payment.drivers.midtrans.server_key');
        $snapUrl = config('payment.drivers.midtrans.snap_url');

        if (empty($serverKey)) {
            // Simulasi dummy sandbox jika API key belum diset
            return [
                'token'        => 'sim-snap-' . uniqid(),
                'redirect_url' => url('/pembayaran/simulasi/' . $tagihan->id),
            ];
        }

        $siswa = $tagihan->siswa;
        $orderId = 'INV-' . $tagihan->id . '-' . time();

        $payload = [
            'transaction_details' => [
                'order_id'     => $orderId,
                'gross_amount' => (int)$tagihan->sisa_tagihan,
            ],
            'customer_details' => [
                'first_name' => $siswa->nama_lengkap ?? 'Siswa',
                'email'      => $siswa->email ?? 'siswa@sinta.id',
                'phone'      => $siswa->no_hp ?? '08123456789',
            ],
            'item_details' => [
                [
                    'id'       => $tagihan->pos_keuangan_id,
                    'price'    => (int)$tagihan->sisa_tagihan,
                    'quantity' => 1,
                    'name'     => $tagihan->pos?->nama_pos ?? 'Tagihan Sekolah',
                ]
            ],
        ];

        try {
            $response = Http::withBasicAuth($serverKey, '')
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($snapUrl, $payload);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error("Midtrans Snap Error: " . $response->body());
            return null;
        } catch (\Throwable $e) {
            Log::error("Gagal createSnapTransaction: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Verifikasi Signature Key Notifikasi Webhook Midtrans
     */
    public function verifyMidtransSignature(string $orderId, string $statusCode, string $grossAmount, string $signatureKey): bool
    {
        $serverKey = config('payment.drivers.midtrans.server_key');
        if (empty($serverKey)) {
            return true; // Bypass untuk testing lokal
        }

        $computedSignature = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);
        return hash_equals($computedSignature, $signatureKey);
    }
}
