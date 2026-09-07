<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\Core\Entities\Tenant;
use Modules\Keuangan\Jobs\GenerateMonthlyInvoicesJob;

class GenerateMonthlyInvoicesCommand extends Command
{
    protected $signature = 'sinta:generate-monthly-invoices {--month=} {--year=} {--tenant=}';
    protected $description = 'Otomatisasi pembuatan invoice tagihan SPP bulanan untuk seluruh sekolah aktif';

    public function handle(): int
    {
        $bulan = (int)($this->option('month') ?: date('n'));
        $tahun = (int)($this->option('year') ?: date('Y'));
        $tenantId = $this->option('tenant');

        $this->info("Memulai pembuatan invoice bulanan SPP: Periode {$bulan}/{$tahun}...");

        $query = Tenant::where('is_active', true);
        if (!empty($tenantId)) {
            $query->where('id', $tenantId);
        }

        $tenants = $query->get();

        foreach ($tenants as $tenant) {
            $this->line("- Mendaftarkan job ke antrean untuk: {$tenant->nama_sekolah} ({$tenant->id})");
            GenerateMonthlyInvoicesJob::dispatch($tenant->id, $bulan, $tahun);
        }

        $this->info("Selesai. {$tenants->count()} sekolah telah dijadwalkan ke worker queue.");
        return Command::SUCCESS;
    }
}
