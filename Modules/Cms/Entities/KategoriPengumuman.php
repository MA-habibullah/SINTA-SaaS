<?php

namespace Modules\Cms\Entities;

use Modules\Core\Entities\BaseTenantModel;

class KategoriPengumuman extends BaseTenantModel
{
    protected $table = 'sistem.kategori_pengumuman';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'tenant_id',
        'nama_kategori',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function pengumuman()
    {
        return $this->hasMany(Pengumuman::class, 'kategori_id');
    }
}
