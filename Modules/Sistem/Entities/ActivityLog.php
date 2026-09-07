<?php

namespace Modules\Sistem\Entities;

use Modules\Core\Entities\BaseTenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Entities\User;

class ActivityLog extends BaseTenantModel
{
    protected $table = 'sistem.activity_logs';

    protected $fillable = [
        'id',
        'tenant_id',
        'user_id',
        'action', // CREATE, UPDATE, DELETE, LOGIN, EXPORT, PRINT
        'module_name',
        'description',
        'ip_address',
        'user_agent',
        'payload_before',
        'payload_after',
    ];

    protected $casts = [
        'payload_before' => 'array',
        'payload_after'  => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
