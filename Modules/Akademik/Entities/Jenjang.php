<?php

namespace Modules\Akademik\Entities;

use Modules\Core\Entities\BaseTenantModel;

class Jenjang extends BaseTenantModel
{
    protected $table = 'core.jenjang';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'tenant_id',
        'kode_jenjang',
        'nama_jenjang',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
