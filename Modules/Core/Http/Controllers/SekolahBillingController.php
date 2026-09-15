<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Core\Entities\Tenant;
use Modules\Core\Entities\TenantSubscriptionInvoice;
use Modules\Core\Services\SecurityPayloadService;

class SekolahBillingController extends Controller
{
    /**
     * Halaman Utama Billing & Langganan Sekolah (Zero-SSR Pattern)
     */
    public function index(Request $request): InertiaResponse|JsonResponse
    {
        // 1. Initial Zero-SSR HTTP GET: Render UI Shell tanpa membocorkan data
        if (!$request->wantsJson() && !$request->boolean('async')) {
            return Inertia::render('Core/Billing/Index', [
                'items'       => null,
                'billingData' => null,
            ]);
        }

        // 2. Asynchronous Client Loading via Axios API (?async=1)
        $user = Auth::user();
        $isSuperAdmin = $user ? $user->isSuperAdmin() : false;
        
        $selectedTenantId = $isSuperAdmin ? $request->query('tenant_id') : null;
        $tenantId = $selectedTenantId ?: (session('tenant_id') ?? $user?->tenant_id);

        $tenant = Tenant::find($tenantId);
        if (!$tenant && $isSuperAdmin) {
            $tenant = Tenant::first();
            $tenantId = $tenant?->id;
        }

        if (!$tenant) {
            return response()->json(['success' => false, 'message' => 'Sekolah tidak ditemukan.'], 404);
        }

        // Auto-Generate Invoice Tagihan Otomatis jika sisa waktu <= 10 hari dan belum ada invoice UNPAID
        $remainingDays = $tenant->remainingSubscriptionDays();
        if ($remainingDays <= 10) {
            $this->ensureUpcomingInvoiceExists($tenant);
        }

        // Filter Invoice
        $status = $request->query('status');
        $search = trim((string)$request->query('search', ''));

        $query = TenantSubscriptionInvoice::where('tenant_id', $tenant->id);

        if (!empty($status)) {
            $query->where('status', strtoupper($status));
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'ILIKE', "%{$search}%")
                  ->orWhere('periode', 'ILIKE', "%{$search}%")
                  ->orWhere('transaction_reference', 'ILIKE', "%{$search}%");
            });
        }

        $invoices = $query->orderBy('created_at', 'desc')->paginate(15);

        // Ringkasan Finansial Tagihan
        $totalPaid = TenantSubscriptionInvoice::where('tenant_id', $tenant->id)->where('status', 'PAID')->sum('nominal');
        $unpaidInvoice = TenantSubscriptionInvoice::where('tenant_id', $tenant->id)->where('status', 'UNPAID')->orderBy('due_date', 'asc')->first();

        $activePlanInfo = [
            'nama_sekolah'               => $tenant->nama_sekolah,
            'npsn'                       => $tenant->npsn,
            'paket_aktif'                => $tenant->paket_aktif ?: 'Enterprise SaaS Pro',
            'subscription_type'          => $tenant->subscription_type ?: 'monthly',
            'billing_cycle'              => $tenant->billing_cycle ?: 'monthly',
            'subscription_price'         => (float)($tenant->subscription_price ?: 750000),
            'subscription_expires_at'    => $tenant->subscription_expires_at ? $tenant->subscription_expires_at->toIso8601String() : null,
            'subscription_expires_label' => $tenant->subscription_expires_at ? $tenant->subscription_expires_at->format('d F Y, H:i') . ' WIB' : 'Belum Diatur',
            'is_subscription_active'     => $tenant->isSubscriptionActive(),
            'remaining_seconds'          => $tenant->remainingSubscriptionSeconds(),
            'remaining_days'             => $remainingDays,
            'is_expiring_soon'           => $tenant->isSubscriptionExpiringSoon(),
            'is_critical'                => $tenant->isSubscriptionCritical(),
            'storage_limit_mb'           => $tenant->storage_limit_mb ?: 10240,
            'max_siswa_limit'            => $tenant->max_siswa_limit ?: 2000,
            'is_locked'                  => (bool)$tenant->is_locked,
            'lock_reason'                => $tenant->lock_reason,
        ];

        $payload = [
            'success'          => true,
            'tenant'           => $activePlanInfo,
            'invoices'         => $invoices,
            'unpaidInvoice'    => $unpaidInvoice,
            'upcoming_invoice' => $unpaidInvoice,
            'totalPaid'        => (float)$totalPaid,
        ];

        return response()->json(SecurityPayloadService::sanitize($payload));
    }

    /**
     * Halaman Peringatan Lockout Khusus saat Masa Langganan Habis
     */
    public function subscriptionExpired(Request $request): InertiaResponse|JsonResponse
    {
        $user = Auth::user();
        $tenantId = session('tenant_id') ?? $user?->tenant_id;
        $tenant = Tenant::find($tenantId);

        $unpaidInvoice = null;
        if ($tenant) {
            $this->ensureUpcomingInvoiceExists($tenant);
            $unpaidInvoice = TenantSubscriptionInvoice::where('tenant_id', $tenant->id)
                ->where('status', 'UNPAID')
                ->orderBy('due_date', 'asc')
                ->first();
        }

        $payload = [
            'tenant' => $tenant ? [
                'id'                      => $tenant->id,
                'nama_sekolah'            => $tenant->nama_sekolah,
                'npsn'                    => $tenant->npsn,
                'subscription_expires_at' => $tenant->subscription_expires_at?->format('d F Y, H:i') . ' WIB',
                'is_locked'               => (bool)$tenant->is_locked,
                'lock_reason'             => $tenant->lock_reason ?: 'Masa aktif paket langganan sekolah telah berakhir.',
            ] : null,
            'unpaidInvoice' => $unpaidInvoice,
            'bank_info' => [
                'bank_name'      => 'Bank Central Asia (BCA)',
                'account_number' => '8830-1928-3900',
                'account_holder' => 'PT SINTA EDUKASI TEKNOLOGI',
            ],
            'support_contact' => [
                'whatsapp' => '+62 812-3456-7890',
                'email'    => 'billing@sinta.id',
            ],
        ];

        if ($request->wantsJson()) {
            return response()->json($payload);
        }

        return Inertia::render('Core/Billing/SubscriptionExpired', $payload);
    }

    /**
     * Konfirmasi Pembayaran Manual Transfer (Upload Bukti Bayar)
     */
    public function submitPayment(Request $request, string $invoiceId): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'payment_method'        => 'required|string|in:TRANSFER_MANUAL,MIDTRANS_VA,QRIS,CREDIT_CARD',
            'transaction_reference' => 'nullable|string|max:100',
            'bukti_transfer'        => 'nullable|image|max:2048', // maks 2MB
            'notes'                 => 'nullable|string|max:500',
        ]);

        $invoice = TenantSubscriptionInvoice::findOrFail($invoiceId);

        $proofPath = null;
        if ($request->hasFile('bukti_transfer')) {
            $file = $request->file('bukti_transfer');
            $filename = 'proof_' . $invoice->invoice_number . '_' . time() . '.' . $file->getClientOriginalExtension();
            $proofPath = $file->storeAs('billing_proofs', $filename, 'public');
        }

        $invoice->update([
            'payment_method'        => $validated['payment_method'],
            'transaction_reference' => $validated['transaction_reference'] ?? ('REF-' . strtoupper(Str::random(8))),
            'payment_proof_path'    => $proofPath ?: $invoice->payment_proof_path,
            'notes'                 => $validated['notes'] ?? $invoice->notes,
            'status'                => 'PAID', // Simulasi auto-verifikasi lunas
            'paid_at'               => now(),
            'verified_by'           => Auth::id(),
        ]);

        // Perpanjang masa aktif tenant
        $tenant = $invoice->tenant;
        if ($tenant) {
            $currentExpiry = $tenant->subscription_expires_at && $tenant->subscription_expires_at->isFuture() 
                ? $tenant->subscription_expires_at 
                : now();

            $newExpiry = (clone $currentExpiry)->addMonths($tenant->billing_cycle === 'annual' ? 12 : 1);

            $tenant->update([
                'subscription_expires_at' => $newExpiry,
                'is_locked'               => false,
                'lock_reason'             => null,
                'status'                  => 'approved',
            ]);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Pembayaran berhasil dikonfirmasi! Masa aktif langganan sekolah telah diperpanjang.',
                'data'    => $invoice,
            ]);
        }

        return back()->with('success', 'Pembayaran berhasil dikonfirmasi! Masa aktif sekolah telah diperpanjang.');
    }

    /**
     * Cetak / Download Lembar Invoice Resmi SaaS (HTML / PDF Preview)
     */
    public function downloadInvoice(string $id)
    {
        $invoice = TenantSubscriptionInvoice::with('tenant')->findOrFail($id);
        $tenant = $invoice->tenant;

        return view('core::billing_invoice', [
            'invoice' => $invoice,
            'tenant'  => $tenant,
        ]);
    }

    /**
     * Helper privat untuk memastikan tagihan terbit 10 hari sebelum expired
     */
    private function ensureUpcomingInvoiceExists(Tenant $tenant): void
    {
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
        }
    }
}
