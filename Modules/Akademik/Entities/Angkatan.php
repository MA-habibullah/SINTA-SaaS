<?php

namespace Modules\Akademik\Entities;

use Modules\Core\Entities\BaseTenantModel;

class Angkatan extends BaseTenantModel
{
    protected $table = 'akademik.angkatan';

    protected $fillable = [
        'id',
        'tenant_id',
        'nama_angkatan',
        'kategori',
        'deskripsi',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
