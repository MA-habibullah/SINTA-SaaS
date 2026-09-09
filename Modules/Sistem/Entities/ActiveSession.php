<?php

namespace Modules\Sistem\Entities;

use Modules\Core\Entities\BaseTenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Entities\User;
use Modules\Core\Entities\Tenant;

class ActiveSession extends BaseTenantModel
{
    protected $table = 'sistem.active_sessions';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'tenant_id',
        'user_id',
        'ip_address',
        'user_agent',
        'tanggal_login',
        'last_activity',
    ];

    protected $casts = [
        'tanggal_login' => 'datetime',
        'last_activity' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }
}
