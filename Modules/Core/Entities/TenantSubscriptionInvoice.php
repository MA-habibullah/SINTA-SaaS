<?php

namespace Modules\Core\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantSubscriptionInvoice extends Model
{
    use HasUuids;

    protected $table = 'core.tenant_subscription_invoices';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = true;

    protected $fillable = [
        'id',
        'tenant_id',
        'invoice_number',
        'periode',
        'nama_paket',
        'nominal',
        'status',
        'due_date',
        'paid_at',
        'payment_method',
        'payment_proof_path',
        'transaction_reference',
        'notes',
        'verified_by',
    ];

    protected $casts = [
        'nominal'    => 'float',
        'due_date'   => 'date',
        'paid_at'    => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id', 'id');
    }

    public static function generateInvoiceNumber(): string
    {
        return 'INV-' . date('Ym') . '-' . strtoupper(substr(uniqid(), -6));
    }

    public function isPaid(): bool
    {
        return strtoupper((string)$this->status) === 'PAID';
    }

    public function isUnpaid(): bool
    {
        return strtoupper((string)$this->status) === 'UNPAID';
    }

    public function isExpired(): bool
    {
        return strtoupper((string)$this->status) === 'EXPIRED';
    }

    public function markAsPaid(string $method = 'manual_transfer', ?string $reference = null, ?string $notes = null, ?string $verifiedBy = null): bool
    {
        $this->status = 'PAID';
        $this->payment_method = $method;
        $this->transaction_reference = $reference;
        $this->paid_at = now();
        if ($notes) {
            $this->notes = $notes;
        }
        if ($verifiedBy) {
            $this->verified_by = $verifiedBy;
        }
        return $this->save();
    }
}
