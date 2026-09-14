<?php

namespace Modules\Perpustakaan\Entities;

use Modules\Core\Entities\BaseTenantModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Entities\Tenant;

class Opname extends BaseTenantModel
{
    protected $table = 'perpustakaan.perpus_opname';

    protected $fillable = [
        'id',
        'tenant_id',
        'nama_perpus_opname',
        'nama_sesi',
        'tanggal_mulai',
        'tanggal_selesai',
        'keterangan',
        'status_opname',
        'kategori',
        'deskripsi',
        'is_active',
    ];

    protected $casts = [
        'tanggal_mulai'   => 'date',
        'tanggal_selesai' => 'date',
        'is_active'       => 'boolean',
        'created_at'      => 'datetime',
        'updated_at'      => 'datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(OpnameItem::class, 'opname_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }
}
