<?php

namespace Modules\Akademik\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Spatie\Browsershot\Browsershot;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Modules\Siswa\Entities\Siswa;
use Modules\Akademik\Entities\Kelas;

class BulkPrintRaporJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 1200; // 20 Menit timeout untuk proses batch rapor

    public function __construct(
        public string $tenantId,
        public string $kelasId,
        public string $tahunAjaranId,
        public string $semester,
        public string $userId
    ) {}

    public function handle(): void
    {
        Log::info("Memulai antrean pencetakan massal rapor kelas: {$this->kelasId} (Tenant: {$this->tenantId})");

        $siswaList = Siswa::withoutTenant()
            ->where('tenant_id', $this->tenantId)
            ->where('kelas_saat_ini', $this->kelasId)
            ->where('status_siswa', 'Aktif')
            ->get();

        $outputDir = "tenants/{$this->tenantId}/rapor/{$this->tahunAjaranId}_{$this->semester}/{$this->kelasId}";
        Storage::disk('local')->makeDirectory($outputDir);

        foreach ($siswaList as $siswa) {
            try {
                $targetPdfPath = storage_path("app/{$outputDir}/Rapor_{$siswa->nisn}_{$siswa->nama_lengkap}.pdf");

                // URL internal render HTML rapor
                $raporHtmlUrl = url("/akademik/rapor/preview-html/{$siswa->id}?semester={$this->semester}&secret=" . config('app.key'));

                Browsershot::url($raporHtmlUrl)
                    ->format('A4')
                    ->margins(15, 15, 15, 15)
                    ->showBackground()
                    ->timeout(60)
                    ->save($targetPdfPath);

                Log::info("Rapor berhasil dicetak untuk siswa: {$siswa->nama_lengkap} ({$siswa->nisn})");
            } catch (\Throwable $e) {
                Log::error("Gagal mencetak rapor siswa ID {$siswa->id}: " . $e->getMessage());
            }
        }

        Log::info("Pencetakan massal rapor selesai untuk {$siswaList->count()} siswa.");
    }
}
