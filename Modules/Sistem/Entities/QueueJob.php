<?php

namespace Modules\Sistem\Entities;

use Modules\Core\Entities\BaseTenantModel;
use Modules\Core\Entities\Tenant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QueueJob extends BaseTenantModel
{
    protected $table = 'sistem.queue_jobs';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'tenant_id',
        'queue',
        'payload',
        'attempts',
        'status',
        'job_type',
        'error_message',
        'available_at',
        'reserved_at',
        'completed_at',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'payload'      => 'array',
        'attempts'     => 'integer',
        'available_at' => 'datetime',
        'reserved_at'  => 'datetime',
        'completed_at' => 'datetime',
        'created_at'   => 'datetime',
        'updated_at'   => 'datetime',
    ];

    /**
     * Relasi ke data tenant / sekolah
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id', 'id');
    }
}
