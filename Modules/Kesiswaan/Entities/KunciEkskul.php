<?php

namespace Modules\Kesiswaan\Entities;

use Modules\Core\Entities\BaseTenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KunciEkskul extends BaseTenantModel
{
    protected $table = 'kesiswaan.kunci_ekskul';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'tenant_id',
        'ekskul_id',
        'tahun_ajaran_id',
        'semester',
        'lock_anggota',
        'lock_nilai',
        'locked_by',
        'locked_at',
        'is_active',
    ];

    protected $casts = [
        'lock_anggota' => 'boolean',
        'lock_nilai'   => 'boolean',
        'is_active'    => 'boolean',
        'locked_at'    => 'datetime',
        'created_at'   => 'datetime',
        'updated_at'   => 'datetime',
    ];

    public function ekskul(): BelongsTo
    {
        return $this->belongsTo(MasterEkskul::class, 'ekskul_id');
    }
}
