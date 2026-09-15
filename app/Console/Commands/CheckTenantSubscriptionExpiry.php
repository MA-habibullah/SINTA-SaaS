<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\Core\Entities\Tenant;
use Modules\Core\Entities\TenantSubscriptionInvoice;

class CheckTenantSubscriptionExpiry extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'sinta:check-subscriptions';

    /**
     * The console command description.
     */
    protected $description = 'Periksa masa aktif langganan seluruh tenant sekolah dan terbitkan tagihan atau lockout otomatis';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info("Memulai pengecekan masa aktif langganan seluruh tenant...");

        $tenants = Tenant::where('id', '!=', '00000000-0000-0000-0000-000000000000')->get();

        $lockedCount = 0;
        $invoicedCount = 0;

        foreach ($tenants as $tenant) {
            $remainingDays = $tenant->remainingSubscriptionDays();
            $isExpired = !$tenant->isSubscriptionActive();

            // 1. Jika expired -> Update Lockout
            if ($isExpired && !$tenant->is_locked) {
                $tenant->update([
                    'is_locked'   => true,
                    'lock_reason' => 'Masa aktif langganan telah berakhir pada ' . ($tenant->subscription_expires_at ? $tenant->subscription_expires_at->format('d M Y') : 'tanggal jatuh tempo'),
                    'status'      => 'suspended',
                ]);
                $lockedCount++;
                $this->warn("🔒 Tenant [{$tenant->nama_sekolah}] (ID: {$tenant->id}) telah DIKUNCI karena masa aktif habis.");
            }

            // 2. Jika <= 10 hari -> Pastikan tagihan berikutnya terbit
            if ($remainingDays <= 10) {
                $hasUnpaid = TenantSubscriptionInvoice::where('tenant_id', $tenant->id)
                    ->where('status', 'UNPAID')
                    ->exists();

                if (!$hasUnpaid) {
                    $nextMonthName = date('F Y', strtotime('+1 month'));
                    $price = (float)($tenant->subscription_price ?: 750000);

                    TenantSubscriptionInvoice::create([
                        'tenant_id'             => $tenant->id,
                        'invoice_number'        => 'INV-' . date('Ym') . '-' . strtoupper(substr($tenant->id, 0, 4)) . '-' . rand(100, 999),
                        'periode'               => 'Langganan Periode ' . $nextMonthName,
                        'nama_paket'            => $tenant->paket_aktif ?: 'Enterprise SaaS Pro',
                        'nominal'               => $price,
                        'status'                => 'UNPAID',
                        'due_date'              => $tenant->subscription_expires_at ? $tenant->subscription_expires_at->format('Y-m-d') : date('Y-m-d', strtotime('+7 days')),
                        'payment_method'        => 'TRANSFER_MANUAL',
                        'notes'                 => 'Tagihan perpanjangan otomatis masa aktif lisensi SaaS SINTA.',
                    ]);
                    $invoicedCount++;
                    $this->info("🧾 Tagihan otomatis berhasil diterbitkan untuk [{$tenant->nama_sekolah}].");
                }
            }
        }

        $this->info("Selesai! {$lockedCount} tenant terkunci, {$invoicedCount} invoice baru diterbitkan.");
        return Command::SUCCESS;
    }
}
