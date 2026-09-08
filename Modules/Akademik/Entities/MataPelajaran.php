<?php

namespace Modules\Akademik\Entities;

use Modules\Core\Entities\BaseTenantModel;

class MataPelajaran extends BaseTenantModel
{
    protected $table = 'akademik.mata_pelajaran';

    protected $fillable = [
        'id',
        'tenant_id',
        'nama_mata_pelajaran',
        'kategori',
        'deskripsi',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
