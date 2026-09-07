<?php

namespace Modules\Akademik\Entities;

use Modules\Core\Entities\BaseTenantModel;

class MataPelajaran extends BaseTenantModel
{
    protected $table = 'akademik.mata_pelajaran';

    protected $fillable = [
        'id',
        'tenant_id',
        'kode_mapel',
        'nama_mapel',
        'kelompok', // 'Umum', 'Kejuruan', 'Pilihan', 'Muatan Lokal'
        'kategori',
        'kkm_default',
        'is_active',
    ];

    protected $casts = [
        'kkm_default' => 'decimal:2',
        'is_active'   => 'boolean',
    ];
}
