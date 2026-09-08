<?php

namespace Modules\Akademik\Entities;

use Modules\Core\Entities\BaseTenantModel;

class ProgramPengajaran extends BaseTenantModel
{
    protected $table = 'akademik.program_pengajaran';

    protected $fillable = [
        'id',
        'tenant_id',
        'kode_program',
        'nama_program',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
