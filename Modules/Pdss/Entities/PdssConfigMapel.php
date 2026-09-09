<?php

namespace Modules\Pdss\Entities;

use Modules\Core\Entities\BaseTenantModel;
use Modules\Akademik\Entities\MataPelajaran;

class PdssConfigMapel extends BaseTenantModel
{
    protected $table = 'pdss.pdss_config_mapel';

    protected $fillable = [
        'id',
        'tenant_id',
        'tahun_ajaran_id',
        'kelas_id',
        'jurusan_id',
        'mapel_id',
        'sem_1',
        'sem_2',
        'sem_3',
        'sem_4',
        'sem_5',
        'sem_6',
        'is_active',
    ];

    protected $casts = [
        'sem_1'     => 'boolean',
        'sem_2'     => 'boolean',
        'sem_3'     => 'boolean',
        'sem_4'     => 'boolean',
        'sem_5'     => 'boolean',
        'sem_6'     => 'boolean',
        'is_active' => 'boolean',
    ];

    public function mataPelajaran()
    {
        return $this->belongsTo(MataPelajaran::class, 'mapel_id', 'id');
    }
}
