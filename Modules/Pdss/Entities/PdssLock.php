<?php

namespace Modules\Pdss\Entities;

use Modules\Core\Entities\BaseTenantModel;

class PdssLock extends BaseTenantModel
{
    protected $table = 'pdss.pdss_lock';

    protected $fillable = [
        'id',
        'tenant_id',
        'nama_pdss_lock',
        'kategori',
        'deskripsi',
        'is_active',
        'step',
        'tahun_ajaran_id',
        'is_locked',
        'locked_by',
        'locked_at',
    ];

    protected $casts = [
        'step'       => 'integer',
        'is_locked'  => 'boolean',
        'is_active'  => 'boolean',
        'locked_at'  => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
