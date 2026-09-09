<?php

namespace Modules\Sistem\Entities;

use Modules\Core\Entities\BaseTenantModel;
use Modules\Core\Entities\Tenant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemError extends BaseTenantModel
{
    protected $table = 'sistem.system_errors';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = true;

    protected $fillable = [
        'id',
        'tenant_id',
        'error_level',
        'message',
        'file',
        'line',
        'trace',
        'request_url',
        'request_method',
        'user_agent',
        'ip_address',
        'context',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'line'       => 'integer',
        'trace'      => 'array',
        'context'    => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id', 'id');
    }
}
