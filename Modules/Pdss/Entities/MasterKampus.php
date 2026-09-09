<?php

namespace Modules\Pdss\Entities;

use Modules\Core\Entities\BaseTenantModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MasterKampus extends BaseTenantModel
{
    protected $table = 'pdss.master_kampus';

    protected $fillable = [
        'id',
        'tenant_id',
        'nama_kampus',
        'jenis',
        'jenis_kampus',
        'akreditasi',
        'kota',
        'kota_kampus',
        'provinsi',
        'alamat',
        'alamat_kampus',
        'id_ptn',
        'kode_ptn',
        'web',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function prodi(): HasMany
    {
        return $this->hasMany(MasterKampusProdi::class, 'kampus_id');
    }
}
