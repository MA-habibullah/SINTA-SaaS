<?php

namespace Modules\Cms\Entities;

use Modules\Core\Entities\BaseTenantModel;

class KategoriAgenda extends BaseTenantModel
{
    protected $table = 'sistem.kategori_agenda';

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
}
