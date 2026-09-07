<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Modules\Core\Entities\Tenant;

class CalculateTenantStorageCommand extends Command
{
    protected $signature = 'sinta:recalculate-storage';
    protected $description = 'Menghitung ulang penggunaan kapasitas penyimpanan disk per-tenant sekolah';

    public function handle(): int
    {
        $this->info("Menghitung penggunaan storage masing-masing tenant...");

        $tenants = Tenant::where('is_active', true)->get();

        foreach ($tenants as $tenant) {
            $tenantPath = storage_path("app/tenants/{$tenant->id}");
            $totalBytes = 0;

            if (File::exists($tenantPath)) {
                $files = File::allFiles($tenantPath);
                foreach ($files as $file) {
                    $totalBytes += $file->getSize();
                }
            }

            $tenant->update(['storage_used_bytes' => $totalBytes]);

            $usedMb = round($totalBytes / (1024 * 1024), 2);
            $this->line("• {$tenant->nama_sekolah}: {$usedMb} MB / {$tenant->storage_limit_mb} MB");
        }

        $this->info("Pembaruan kuota penyimpanan selesai.");
        return Command::SUCCESS;
    }
}
