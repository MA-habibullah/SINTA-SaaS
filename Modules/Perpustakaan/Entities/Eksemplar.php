<?php

namespace Modules\Perpustakaan\Entities;

use Modules\Core\Entities\BaseTenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Entities\Tenant;

class Eksemplar extends BaseTenantModel
{
    protected $table = 'perpustakaan.perpus_eksemplar';

    protected $fillable = [
        'id',
        'tenant_id',
        'bibliografi_id',
        'nama_perpus_eksemplar',
        'barcode',
        'no_induk',
        'lokasi_rak',
        'status_kondisi',
        'sumber_perolehan',
        'harga_beli',
        'kategori',
        'deskripsi',
        'is_active',
    ];

    protected $casts = [
        'harga_beli' => 'decimal:2',
        'is_active'  => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function buku(): BelongsTo
    {
        return $this->belongsTo(Buku::class, 'bibliografi_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }
}
