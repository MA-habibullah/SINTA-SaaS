<?php

namespace Modules\Kesiswaan\Entities;

use Modules\Core\Entities\BaseTenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Siswa\Entities\Siswa;

class NilaiEkskul extends BaseTenantModel
{
    protected $table = 'kesiswaan.nilai_ekskul';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'tenant_id',
        'ekskul_id',
        'siswa_id',
        'tahun_ajaran_id',
        'semester',
        'predikat',
        'nilai_angka',
        'keterangan',
        'is_locked',
        'is_active',
    ];

    protected $casts = [
        'nilai_angka' => 'float',
        'is_locked'   => 'boolean',
        'is_active'   => 'boolean',
        'created_at'  => 'datetime',
        'updated_at'  => 'datetime',
    ];

    public function ekskul(): BelongsTo
    {
        return $this->belongsTo(MasterEkskul::class, 'ekskul_id');
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'siswa_id');
    }
}
