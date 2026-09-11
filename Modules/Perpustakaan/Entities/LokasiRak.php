<?php

namespace Modules\Perpustakaan\Entities;

use Modules\Core\Entities\BaseTenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Entities\Tenant;

class LokasiRak extends BaseTenantModel
{
    protected $table = 'perpustakaan.perpus_lokasi_rak';

    protected $fillable = [
        'id',
        'tenant_id',
        'nama_perpus_lokasi_rak',
        'kode_rak',
        'nama_rak',
        'lantai_gedung',
        'kapasitas_buku',
        'kategori',
        'deskripsi',
        'is_active',
    ];

    protected $casts = [
        'kapasitas_buku' => 'integer',
        'is_active'      => 'boolean',
        'created_at'     => 'datetime',
        'updated_at'     => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }
}
