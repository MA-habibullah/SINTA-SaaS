<?php

namespace Modules\Akademik\Entities;

use Modules\Core\Entities\BaseTenantModel;

class Pendidikan extends BaseTenantModel
{
    protected $table = 'akademik.pendidikan';

    protected $fillable = [
        'id',
        'tenant_id',
        'nama_pendidikan',
        'kategori',
        'deskripsi',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
