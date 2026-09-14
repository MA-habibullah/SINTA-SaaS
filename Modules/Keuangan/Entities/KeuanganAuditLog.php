<?php

namespace Modules\Keuangan\Entities;

use Modules\Core\Entities\BaseTenantModel;
use Modules\Core\Entities\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KeuanganAuditLog extends BaseTenantModel
{
    protected $table = 'keuangan.transaksi_spp_audit_log';

    protected $fillable = [
        'id',
        'tenant_id',
        'user_id',
        'user_role',
        'event_type', // 'PAYMENT', 'VOID', 'GENERATE', 'EDIT_NOMINAL', 'DELETE'
        'nominal',
        'old_data',
        'new_data',
        'ip_address',
        'user_agent',
        'keterangan',
        'is_active',
    ];

    protected $casts = [
        'nominal'   => 'decimal:2',
        'old_data'  => 'array',
        'new_data'  => 'array',
        'is_active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
