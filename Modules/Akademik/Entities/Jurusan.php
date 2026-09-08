<?php

namespace Modules\Akademik\Entities;

use Modules\Core\Entities\BaseTenantModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Jurusan extends BaseTenantModel
{
    protected $table = 'akademik.jurusan';

    protected $fillable = [
        'id',
        'tenant_id',
        'nama_jurusan',
        'kategori',
        'deskripsi',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function kelas(): HasMany
    {
        return $this->hasMany(Kelas::class, 'id_jurusan', 'id');
    }
}
