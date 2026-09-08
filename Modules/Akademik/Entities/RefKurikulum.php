<?php

namespace Modules\Akademik\Entities;

use Modules\Core\Entities\BaseTenantModel;

class RefKurikulum extends BaseTenantModel
{
    protected $table = 'akademik.ref_kurikulum';

    protected $fillable = [
        'id',
        'tenant_id',
        'nama_ref_kurikulum',
        'kategori',
        'deskripsi',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
