<?php

namespace App\Services\Notification;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppGatewayService
{
    protected string $apiKey;
    protected string $apiUrl;

    public function __construct()
    {
        $this->apiKey = env('WA_GATEWAY_API_KEY', '');
        $this->apiUrl = env('WA_GATEWAY_URL', 'https://api.fonnte.com/send');
    }

    /**
     * Kirim pesan teks WhatsApp ke nomor tujuan
     */
    public function sendMessage(string $targetPhone, string $message): bool
    {
        if (empty($this->apiKey)) {
            Log::info("[WA Gateway Sim] Kirim ke {$targetPhone}: {$message}");
            return true;
        }

        try {
            $formattedPhone = $this->formatPhoneNumber($targetPhone);
            $response = Http::withHeaders([
                'Authorization' => $this->apiKey,
            ])->post($this->apiUrl, [
                'target'  => $formattedPhone,
                'message' => $message,
            ]);

            return $response->successful();
        } catch (\Throwable $e) {
            Log::error("Gagal mengirim WhatsApp ke {$targetPhone}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Notifikasi Pembayaran SPP ke Orang Tua
     */
    public function sendPaymentReceiptNotification(string $noHp, string $namaSiswa, string $namaPos, float $nominal, string $nomorTrx): bool
    {
        $nominalRupiah = 'Rp ' . number_format($nominal, 0, ',', '.');
        $waktu = \Carbon\Carbon::now()->translatedFormat('d F Y H:i');

        $message = "📢 *KONFIRMASI PEMBAYARAN SINTA*\n\n"
            . "Yth. Orang Tua/Wali Murid,\n"
            . "Terima kasih, pembayaran untuk:\n"
            . "• *Nama Siswa*: {$namaSiswa}\n"
            . "• *Jenis Tagihan*: {$namaPos}\n"
            . "• *Jumlah Bayar*: *{$nominalRupiah}*\n"
            . "• *No. Transaksi*: `{$nomorTrx}`\n"
            . "• *Waktu*: {$waktu}\n\n"
            . "Telah *BERHASIL* diverifikasi oleh sistem keuangan sekolah.\n\n"
            . "_Pesan otomatis oleh Sistem Informasi Akademik Terpadu (SINTA)._";

        return $this->sendMessage($noHp, $message);
    }

    /**
     * Format nomor ponsel ke standar internasional (62xxx)
     */
    private function formatPhoneNumber(string $phone): string
    {
        $clean = preg_replace('/[^0-9]/', '', $phone);
        if (str_starts_with($clean, '0')) {
            return '62' . substr($clean, 1);
        }
        if (str_starts_with($clean, '8')) {
            return '62' . $clean;
        }
        return $clean;
    }
}
