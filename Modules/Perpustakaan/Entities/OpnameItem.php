<?php

namespace Modules\Perpustakaan\Entities;

use Modules\Core\Entities\BaseTenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Entities\Tenant;

class OpnameItem extends BaseTenantModel
{
    protected $table = 'perpustakaan.perpus_opname_item';

    protected $fillable = [
        'id',
        'tenant_id',
        'opname_id',
        'eksemplar_id',
        'barcode',
        'status_temuan',
        'kondisi_fisik',
        'scanned_at',
        'petugas_scan',
        'is_active',
    ];

    protected $casts = [
        'scanned_at' => 'datetime',
        'is_active'  => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function opname(): BelongsTo
    {
        return $this->belongsTo(Opname::class, 'opname_id');
    }

    public function eksemplar(): BelongsTo
    {
        return $this->belongsTo(Eksemplar::class, 'eksemplar_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }
}
