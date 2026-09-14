<?php

namespace Modules\Perpustakaan\Entities;

use Modules\Core\Entities\BaseTenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Entities\Tenant;

class KategoriDdc extends BaseTenantModel
{
    protected $table = 'perpustakaan.perpus_kategori_ddc';

    protected $fillable = [
        'id',
        'tenant_id',
        'nama_perpus_kategori_ddc',
        'kode_ddc',
        'nama_klasifikasi',
        'warna_label',
        'deskripsi_ddc',
        'kategori',
        'deskripsi',
        'is_active',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }
}
