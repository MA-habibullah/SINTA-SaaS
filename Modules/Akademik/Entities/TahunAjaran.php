<?php

namespace Modules\Akademik\Entities;

use Modules\Core\Entities\BaseTenantModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TahunAjaran extends BaseTenantModel
{
    protected $table = 'akademik.tahun_ajaran';

    protected $fillable = [
        'id',
        'tenant_id',
        'nama_tahun_ajaran', // e.g. 2026/2027
        'semester',          // 'Ganjil', 'Genap'
        'is_active',
        'tanggal_mulai',
        'tanggal_selesai',
    ];

    protected $casts = [
        'is_active'       => 'boolean',
        'tanggal_mulai'   => 'date',
        'tanggal_selesai' => 'date',
    ];

    public function kelas(): HasMany
    {
        return $this->hasMany(Kelas::class, 'tahun_ajaran_id', 'id');
    }
}
