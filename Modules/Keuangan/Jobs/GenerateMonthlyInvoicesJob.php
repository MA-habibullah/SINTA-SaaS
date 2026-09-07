<?php

namespace Modules\Keuangan\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Siswa\Entities\Siswa;
use Modules\Keuangan\Entities\PosKeuangan;
use Modules\Keuangan\Entities\TarifPembayaran;
use Modules\Keuangan\Entities\TagihanSiswa;

class GenerateMonthlyInvoicesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $tenantId,
        public int $bulan,
        public int $tahun,
        public ?string $tahunAjaranId = null
    ) {}

    public function handle(): void
    {
        Log::info("Memulai pembuatan invoice tagihan bulanan SPP: Bulan {$this->bulan}/{$this->tahun} untuk Tenant: {$this->tenantId}");

        // 1. Ambil pos keuangan SPP Bulanan
        $posSpp = PosKeuangan::withoutTenant()
            ->where('tenant_id', $this->tenantId)
            ->where('tipe_pembayaran', 'Bulanan')
            ->where('is_active', true)
            ->first();

        if (!$posSpp) {
            Log::warning("Pos SPP bulanan tidak ditemukan untuk tenant {$this->tenantId}.");
            return;
        }

        // 2. Ambil seluruh siswa aktif
        $siswaAktif = Siswa::withoutTenant()
            ->where('tenant_id', $this->tenantId)
            ->where('status_siswa', 'Aktif')
            ->get();

        $generatedCount = 0;

        foreach ($siswaAktif as $siswa) {
            // Cek apakah tagihan bulan ini sudah ada
            $alreadyExists = TagihanSiswa::withoutTenant()
                ->where('tenant_id', $this->tenantId)
                ->where('siswa_id', $siswa->id)
                ->where('pos_keuangan_id', $posSpp->id)
                ->where('bulan', $this->bulan)
                ->where('tahun', $this->tahun)
                ->exists();

            if ($alreadyExists) {
                continue;
            }

            // Ambil nominal tarif pembayaran untuk tingkat siswa
            $tarif = TarifPembayaran::withoutTenant()
                ->where('tenant_id', $this->tenantId)
                ->where('pos_keuangan_id', $posSpp->id)
                ->first();

            $nominal = $tarif ? $tarif->nominal : 250000; // Default nominal

            TagihanSiswa::create([
                'tenant_id'           => $this->tenantId,
                'siswa_id'            => $siswa->id,
                'pos_keuangan_id'     => $posSpp->id,
                'tahun_ajaran_id'     => $this->tahunAjaranId,
                'bulan'               => $this->bulan,
                'tahun'               => $this->tahun,
                'nomor_tagihan'       => 'INV/' . $this->tahun . '/' . str_pad($this->bulan, 2, '0', STR_PAD_LEFT) . '/' . rand(10000, 99999),
                'total_tagihan'       => $nominal,
                'total_terbayar'      => 0,
                'sisa_tagihan'        => $nominal,
                'status_pembayaran'   => 'Belum Bayar',
                'tanggal_jatuh_tempo' => now()->setDate($this->tahun, $this->bulan, 10)->toDateString(),
            ]);

            $generatedCount++;
        }

        Log::info("Selesai generate invoice bulanan. Berhasil membuat {$generatedCount} tagihan siswa baru.");
    }
}
