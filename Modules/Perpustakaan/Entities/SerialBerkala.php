<?php

namespace Modules\Perpustakaan\Entities;

use Modules\Core\Entities\BaseTenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Entities\Tenant;

class SerialBerkala extends BaseTenantModel
{
    protected $table = 'perpustakaan.perpus_serial_berkala';

    protected $fillable = [
        'id',
        'tenant_id',
        'nama_perpus_serial_berkala',
        'nama_serial',
        'jenis_serial',
        'issn',
        'edisi_nomor',
        'frekuensi_terbit',
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
